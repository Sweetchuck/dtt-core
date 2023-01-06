<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\System;

use Behat\Mink\Element\NodeElement;
use PHPUnit\Framework\Assert;

trait LocalActionTrait {

  /**
   * @return \Behat\Mink\Session
   *
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  abstract protected function getSession();

  /**
   * @abstract
   *
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  protected array $finders = [];

  /**
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  abstract protected function getFinderSettings(): array;

  /**
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  abstract protected function getFinder(string $finderName, array $args = []): array;

  /**
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  abstract protected function isFinderExists(string $finderName): bool;

  /**
   * @return $this
   */
  protected function initFindersDrupalCoreSystemLocalAction() {
    $this->finders += [
      'drupal.core.local_action.wrapper' => [
        'selector' => 'css',
        'locator' => '.local-actions',
      ],
      'drupal.core.local_action.links' => [
        'selector' => 'css',
        'locator' => 'a',
      ],
    ];

    return $this;
  }

  public function assertDrupalCoreSystemLocalActionsCount(int $amount) {
    Assert::assertCount(
      $amount,
      $this->getDrupalCoreSystemLocalActions(),
    );
  }

  public function assertDrupalCoreSystemLocalActionsEmpty() {
    Assert::assertEmpty(
      $this->getDrupalCoreSystemLocalActions(),
      'There is no any local action'
    );
  }

  /**
   * @return \Behat\Mink\Element\NodeElement[]
   */
  public function getDrupalCoreSystemLocalActions(): array {
    $wrapper_finder = $this->getFinder('drupal.core.local_action.wrapper');
    $wrapper = $this
      ->getSession()
      ->getPage()
      ->find($wrapper_finder['selector'], $wrapper_finder['locator']);

    if (!$wrapper) {
      return [];
    }

    $items_finder = $this->getFinder('drupal.core.local_action.links');

    return $wrapper->findAll($items_finder['selector'], $items_finder['locator']);
  }

  public function getDrupalCoreSystemLocalActionByLabel(string $label): ?NodeElement {
    $links = [];
    foreach ($this->getDrupalCoreSystemLocalActions() as $link) {
      if ($link->getText() === $label) {
        $links[] = $link;
      }
    }

    if (!$links) {
      return NULL;
    }

    if (count($links) > 1) {
      // @todo Log a warning about multiple links with the same text.
    }

    return $links[0];
  }

}
