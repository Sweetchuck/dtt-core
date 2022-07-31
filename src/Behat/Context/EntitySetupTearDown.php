<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Drupal;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Filesystem\Filesystem;

class EntitySetupTearDown extends Base {

  protected ?array $entityTypeWeights = NULL;

  public function getDefaultEntityTypeWeights(): array {
    return array_flip([
      'block_content',

      'node',
      'profile',
      'search_api_task',

      'commerce_order',
      'commerce_order_item',
      'commerce_shipment',
      'commerce_product',
      'commerce_product_variation',
      'commerce_product_attribute_value',
      'commerce_store',
      'commerce_shipping_method',
      'commerce_payment',
      'commerce_payment_method',
      'commerce_log',

      'content_moderation_state',
      'media',
      'file',
      'crop',
      'user',

      'taxonomy_term',
      'mailchimp_campaign',
      'menu_link_content',
    ]);
  }

  public function getEntityTypeWeights(): array {
    if ($this->entityTypeWeights === NULL) {
      $container = $this->getContainer();
      $parameterName = 'sweetchuck.core.entity_type_weights';
      $weights = $container->hasParameter($parameterName) ? $container->getParameter($parameterName) : [];

      $this->entityTypeWeights = $weights + $this->getDefaultEntityTypeWeights();
    }

    return $this->entityTypeWeights;
  }

  public function compareEntityTypesByWeight(string $a, string $b): int {
    $weights = $this->getEntityTypeWeights();

    return ($weights[$a] ?? 0) <=> ($weights[$b] ?? 0);
  }

  /**
   * File names.
   *
   * @var string[]
   */
  protected array $unManagedFiles = [];

  /**
   * Existing Entity IDs before the scenario.
   *
   * - null: There are no instances.
   * - int: Highest numeric ID.
   * - string[]: Existing entity IDs.
   *
   * @var int[]|string[][]|null[]
   */
  protected array $latestContentEntityIds = [];

  /**
   * @BeforeScenario
   */
  public function hookBeforeScenario() {
    try {
      $this->visitPath('/core/misc/favicon.ico');
    }
    catch (\Exception $e) {
      // Do nothing.
    }

    $this->initLatestContentEntityIds();
  }

  /**
   * @AfterScenario
   */
  public function hookAfterScenario() {
    $this
      ->cleanNewContentEntities()
      ->cleanUnManagedFiles();
  }

  /**
   * @return $this
   */
  protected function initLatestContentEntityIds() {
    $etm = Drupal::entityTypeManager();
    foreach ($etm->getDefinitions() as $entityType) {
      if (!$this->isContentEntityType($entityType)) {
        continue;
      }

      $id = $this->getLatestContentEntityId($entityType);
      $this->latestContentEntityIds[$entityType->id()] = $id;
      if ($id === NULL) {
        continue;
      }

      // @todo Instance with string ID can be numeric as well.
      // Check the type of the ID field.
      if (preg_match('/^\d+$/', (string) $id)) {
        $this->latestContentEntityIds[$entityType->id()] = (int) $id;

        continue;
      }

      $this->latestContentEntityIds[$entityType->id()] = $etm
        ->getStorage($entityType->id())
        ->getQuery()
        ->execute();
    }

    return $this;
  }

  /**
   * @return null|int|string
   */
  protected function getLatestContentEntityId(EntityTypeInterface $entityType) {
    $etm = Drupal::entityTypeManager();

    if ($entityType->id() === 'group') {
      // @todo Something wrong with "group" entity type.
      $all = $etm
        ->getStorage($entityType->id())
        ->loadMultiple(NULL);

      ksort($all, \SORT_NUMERIC);
      $entity = end($all);

      return $entity ? $entity->id() : NULL;
    }

    /** @var int[]|string[] $ids */
    $ids = $etm
      ->getStorage($entityType->id())
      ->getQuery()
      ->sort($entityType->getKey('id'), 'DESC')
      ->range(0, 1)
      ->execute();
    $id = reset($ids);

    return $id === FALSE ? NULL : $id;
  }

  /**
   * @return $this
   */
  protected function cleanNewContentEntities() {
    $etm = Drupal::entityTypeManager();
    uksort($this->latestContentEntityIds, [static::class, 'compareEntityTypesByWeight']);
    foreach ($this->latestContentEntityIds as $entityTypeId => $entityId) {
      if (!$etm->hasDefinition($entityTypeId)) {
        continue;
      }

      $entityType = $etm->getDefinition($entityTypeId);
      $storage = $etm->getStorage($entityTypeId);

      $query = $storage->getQuery();

      if ($entityId === NULL) {
        $storage->resetCache();
        $storage->delete($storage->loadMultiple());

        continue;
      }

      $idsToDelete = $query
        ->condition(
          $entityType->getKey('id'),
          $entityId,
          is_array($entityId) ? 'NOT IN' : '>'
        )
        ->execute();

      if ($idsToDelete) {
        $storage->resetCache($idsToDelete);
        $storage->delete($storage->loadMultiple($idsToDelete));
      }
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function cleanUnManagedFiles() {
    $fs = new Filesystem();
    $drupalRoot = Drupal::root();
    while (($fileName = array_pop($this->unManagedFiles))) {
      $fs->remove("$drupalRoot/$fileName");
    }

    return $this;
  }

  protected function isContentEntityType(EntityTypeInterface $entityType): bool {
    return $entityType->hasKey('id') && $entityType->getBaseTable();
  }

}
