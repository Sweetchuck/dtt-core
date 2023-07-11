<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Mink\Element\Element;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;

class DropButton extends Base {

  protected function initFinders() {
    parent::initFinders()
      ->initFindersDrupalCoreDropButton();

    return $this;
  }

  protected function initFindersDrupalCoreDropButton(): static {
    $this->finders += [
      'drupal.core.dropbutton.wrapper' => [
        'selector' => 'css',
        'locator' => '.dropbutton-wrapper',
      ],
    ];

    return $this;
  }

  /**
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function findDropButtonWrapper(NodeElement $parent, bool $required = FALSE): ?NodeElement {
    $finder = $this->getFinder('drupal.core.dropbutton.wrapper');

    $element = $parent->find($finder['selector'], $finder['locator']);
    if (!$element && $required) {
      throw new ElementNotFoundException(
        $this->getSession(),
        NULL,
        $finder['selector'],
        $finder['locator'],
      );
    }

    return $element;
  }

}
