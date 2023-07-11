<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Sweetchuck\DrupalTestTraits\Core\Utils;

class Table extends Base {

  protected function initFinders() {
    parent::initFinders()
      ->initFindersDrupalCoreTable();

    return $this;
  }

  protected function initFindersDrupalCoreTable(): static {
    $this->finders += [
      'drupal.core.table.wrapper' => [
        'selector' => 'css',
        'locator' => '.dropbutton-wrapper',
      ],
    ];

    return $this;
  }

  /**
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function findTableById(string $id, bool $required = FALSE): ?NodeElement {
    // @todo Validate or escape $id.
    $selector = 'css';
    $locator = "table#$id";
    $tableElement = $this
      ->getSession()
      ->getPage()
      ->find($selector, $locator);

    if (!$tableElement && $required) {
      throw new ElementNotFoundException(
        $this->getSession(),
        NULL,
        $selector,
        $locator,
      );
    }

    return $tableElement;
  }

  /**
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function getColumnIndexByHeader(NodeElement $tableElement, string $cellContent, bool $required = FALSE): ?int {
    $selector = 'xpath';
    $locator = strtr(
      './thead/tr/th[normalize-space(text()) = "{{ cellContent }}"]',
      [
        '{{ cellContent }}' => Utils::escapeXpathValue($cellContent),
      ],
    );
    $cells = $tableElement->findAll($selector, $locator);
    $index = 1;
    foreach ($cells as $cellElement) {
      if (trim($cellElement->getText()) === $cellContent) {
        return $index;
      }

      $index++;
    }

    if ($required) {
      throw new ElementNotFoundException(
        $this->getSession(),
        NULL,
        $selector,
        $locator,
      );
    }

    return NULL;
  }

  /**
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function findRowByCellContent(
    NodeElement $tableElement,
    int $cellIndex,
    string $cellContent,
    bool $required = FALSE,
  ): ?NodeElement {
    $rowElements = $tableElement->findAll('xpath', './tbody/tr');
    // @todo Make the finder configurable.
    $finder1 = [
      'selector' => 'xpath',
      'locator' => strtr(
        './td[position() = {{ cellIndex }} and normalize-space(text()) = "{{ cellContent }}"]',
        [
          '{{ cellIndex }}' => $cellIndex,
          '{{ cellContent }}' => Utils::escapeXpathValue($cellContent),
        ],
      ),
    ];

    // @todo Make the finder configurable.
    $finder2 = [
      'selector' => 'css',
      'locator' => 'td .tabledrag-cell-content__item',
    ];

    foreach ($rowElements as $rowElement) {
      $cellElement = $rowElement->find($finder1['selector'], $finder1['locator']);
      if ($cellElement) {
        return $rowElement;
      }

      $cellElement = $rowElement->find($finder2['selector'], $finder2['locator']);
      if ($cellElement && trim($cellElement->getText()) === $cellContent) {
        return $rowElement;
      }
    }

    if ($required) {
      throw new ElementNotFoundException(
        $this->getSession(),
        NULL,
        $finder1['selector'],
        $finder1['locator'],
      );
    }

    return NULL;
  }

}
