<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url as DrupalUrl;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Assert;

class Entity extends Base {

  /**
   * @param string $relation
   *   For the allowed values check the "links" part of any of the
   *   ContentEntityType or ConfigEntityType annotations.
   *
   * @Given I am on the :relation page of :label :entityTypeId
   * @When  I go to the :relation page of :label :entityTypeId
   *
   * @see \Drupal\node\Entity\Node
   * @see \Drupal\user\Entity\User
   */
  public function doGoToEntityUrl(string $entityTypeId, string $label, string $relation) {
    $url = $this->getEntityUrlByLabel($entityTypeId, $label, $relation);
    Assert::assertNotEmpty(
      $url,
      sprintf('No product with "%s" title is exists.', $label)
    );

    $this->visitPath($url);
  }

  public function getEntityByLabel(
    string $entityTypeId,
    string $label,
    string $fieldName = ''
  ): ?EntityInterface {
    $etm = \Drupal::entityTypeManager();
    $storage = $etm->getStorage($entityTypeId);
    $entityType = $etm->getDefinition($entityTypeId);

    if (!$fieldName) {
      switch ($entityTypeId) {
        case 'user':
          $fieldName = 'name';
          break;

        default:
          $fieldName = $entityType->getKey('label');
          break;
      }
    }

    // https://www.drupal.org/project/drupal/issues/2986322
    $storage->resetCache();

    $entities = $storage
      ->loadByProperties(
        [
          $fieldName => $label,
        ],
      );

    // @todo Multiple result.
    $entity = reset($entities);

    return $entity ?: NULL;
  }

  protected function getEntityUrlByLabel(
    string $entityTypeId,
    string $label,
    $relation = 'canonical',
    $options = []
  ): ?string {
    $entity = $this->getEntityByLabel($entityTypeId, $label);
    if (!$entity) {
      return NULL;
    }

    $relationParts = explode('/', $relation);
    $url = $entity->toUrl(array_shift($relationParts), $options);
    if (!$relationParts) {
      return $url->toString();
    }

    // @todo This path will not include any prefixes, fragments, or query strings.
    $internalPath = '/' . $url->getInternalPath() . '/' . implode('/', $relationParts);
    $url = DrupalUrl::fromUserInput($internalPath);

    return $url->toString();
  }

  public function getEntityOperationLink(EntityInterface $entity, $operation): ?NodeElement {
    $element = $this->getSession()->getPage();
    $locator = ($operation ? ['link', sprintf("'%s'", $operation)] : ['link', "."]);

    $replacementPairs = [
      'edit' => 'edit-form',
      'delete' => 'delete-form',
    ];
    $op = strtr($operation, $replacementPairs);
    /** @var \Behat\Mink\Element\NodeElement[] $links */
    $links = $element->findAll('named', $locator);
    // Loop over all the links on the page and check for the entity
    // operation path.
    foreach ($links as $result) {
      $target = $result->getAttribute('href');
      if (strpos($target, $entity->toUrl($op)->setAbsolute(FALSE)->toString()) !== FALSE) {
        return $result;
      }
    }

    return NULL;
  }

  public function createContentEntity(
    string $entityTypeId,
    array $fieldValues
  ): ContentEntityInterface {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $efm */
    $efm = \Drupal::service('entity_field.manager');
    $etm = \Drupal::entityTypeManager();
    $entityType = $etm->getDefinition($entityTypeId);

    $bundleKey = $entityType->hasKey('id') ?
      $entityType->getKey('bundle')
      : '';

    $bundleId = $fieldValues[$bundleKey] ?? $entityTypeId;

    $baseFields = $efm->getBaseFieldDefinitions($entityTypeId);
    $fields = $efm->getFieldDefinitions($entityTypeId, $bundleId);

    $values = [];
    foreach ($fieldValues as $fieldName => $fieldValue) {
      $fieldId = "$entityTypeId:$bundleId:$fieldName";
      $field = $baseFields[$fieldName] ?? $fields[$fieldName];

      switch ($fieldId) {
        case 'commerce_product:moc:variations':
          $values[$fieldName] = $fieldValue;
          break;
      }

      if (isset($values[$fieldName])) {
        continue;
      }

      // @todo Process values based on the type of the destination field.
      switch ($field->getType()) {
        case 'entity_reference':
          if ($fieldName === $bundleKey) {
            // @todo Do the same if the targetEntityTypeId is a ConfigEntity.
            $values[$fieldName] = $fieldValue;
          }
          else {
            $targetEntityTypeId = $field->getSetting('target_type');
            if (!is_array($fieldValue)) {
              $fieldValue = [$fieldValue];
            }

            foreach (array_keys($fieldValue) as $delta) {
              $values[$fieldName][$delta] = $this->getEntityByLabel(
                $targetEntityTypeId,
                $fieldValue[$delta]
              )->id();
            }
          }
          break;

        case 'file':
          $targetEntityTypeId = 'file';
          if (!is_array($fieldValue)) {
            $fieldValue = [$fieldValue];
          }

          foreach (array_keys($fieldValue) as $delta) {
            $values[$fieldName][$delta] = $this->getEntityByLabel(
              $targetEntityTypeId,
              $fieldValue[$delta]
            )->id();
          }
          break;

        default:
          $values[$fieldName] = $fieldValue;
          break;
      }
    }

    /** @var ContentEntityInterface $contentEntity */
    $contentEntity = \Drupal
      ::entityTypeManager()
      ->getStorage($entityTypeId)
      ->create($values);

    $contentEntity->save();

    return $contentEntity;
  }

  /**
   * @return \Drupal\field\FieldStorageConfigInterface[]
   */
  protected function getFields(string $entityTypeId): array {
    $fields = [];

    $allFields = FieldStorageConfig::loadMultiple();
    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    foreach ($allFields as $field) {
      if ($field->getTargetEntityTypeId() !== $entityTypeId) {
        continue;
      }

      $fields[$field->id()] = $field;
    }

    return $fields;
  }

  protected function keyValuePairsToNestedArray(array $keyValuePairs): array {
    $values = [];
    foreach ($keyValuePairs as $keyParts => $value) {
      $parents = explode(':', $keyParts);
      $values = array_replace_recursive($values, $this->buildNestedArray($parents, $value));
    }

    return $values;
  }

  protected function buildNestedArray(array $parents, $value): array {
    $key = array_shift($parents);

    return [
      $key => $parents ? $this->buildNestedArray($parents, $value) : $value,
    ];
  }

}
