<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Sweetchuck\DrupalTestTraits\Core\Utils;

class Datetime extends Base {

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    return parent::initFinders()
      ->initFindersDrupalCoreDatetime();
  }

  protected function initFindersDrupalCoreDatetime() {
    $this->finders += [
      'drupal.core.datetime.field_widget.default.wrappers' => [
        'selector' => 'css',
        'locator' => '.field--type-daterange.field--widget-daterange-default',
      ],
      'drupal.core.datetime.field_widget.default.label' => [
        'selector' => 'xpath',
        'locator' => './fieldset/legend/span[normalize-space(text()) = "{{ fieldLabel }}"]',
      ],
    ];

    return $this;
  }

  /**
   * @When I fill :field date range field with:
   *
   * @code
   * When I fill "My datetime label" date range field with:
   *   | from                | to                  |
   *   | 2023-11-26T01:02:03 | 2023-11-27T04:05:06 |
   * @endcode
   *
   * @code
   * When I fill "My date label" date range field with:
   *   | from       | to         |
   *   | 2023-11-26 | 2023-11-27 |
   * @endcode
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function doDateRangeFieldWidgetDefaultFill(string $field, TableNode $table) {
    $baseDatetime = new \DateTime();
    $wrappers = $this->getDateRangeFieldWidgetDefaultWrappers();
    $wrapper = $this->findDateRangeFieldWidgetDefaultWrapperByFieldLabel($field, $wrappers);
    if (!$wrapper) {
      // @todo Find it by field machine-name.
      throw new ExpectationException(
        'Date range fieldWidget.default element not found',
        $this->getSession(),
      );
    }

    $values = $this->convertDateRangeTableNodeToFieldValues($baseDatetime, $table);
    $this->dateRangeFieldWidgetDefaultFillValues($values, $wrapper);
  }

  public function convertDateRangeTableNodeToFieldValues(\Datetime $baseDatetime, TableNode $tableNode): array {
    $format = 'Y-m-d\TH:i:s';
    $values = [];
    foreach ($tableNode->getColumnsHash() as $row) {
      $values[] = [
        'value' => $row['from'] === '' ? NULL : date($format, strtotime($row['from'], $baseDatetime->getTimestamp())),
        'end_value' => $row['to'] === '' ? NULL : date($format, strtotime($row['to'], $baseDatetime->getTimestamp())),
      ];
    }

    return $values;
  }

  /**
   * @return \Behat\Mink\Element\NodeElement[]
   */
  public function getDateRangeFieldWidgetDefaultWrappers(?ElementInterface $root = NULL): array {
    if (!$root) {
      $root = $this->getSession()->getPage();
    }

    $finder = $this->getFinder('drupal.core.datetime.field_widget.default.wrappers');

    return $root->findAll($finder['selector'], $finder['locator']);
  }

  /**
   * @param string $fieldLabel
   * @param \Behat\Mink\Element\NodeElement[] $wrappers
   */
  public function findDateRangeFieldWidgetDefaultWrapperByFieldLabel(string $fieldLabel, array $wrappers): ?NodeElement {
    $finder = $this->getFinder(
      'drupal.core.datetime.field_widget.default.label',
      [
        '{{ fieldLabel }}' => Utils::escapeXpathValue($fieldLabel),
      ],
    );

    foreach ($wrappers as $wrapper) {
      $element = $wrapper->find($finder['selector'], $finder['locator']);
      if ($element) {
        return $wrapper;
      }
    }

    return NULL;
  }

  /**
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function dateRangeFieldWidgetDefaultFillValues(array $values, NodeElement $wrapper) {
    foreach ($values as $delta => $value) {
      $this->dateRangeFieldWidgetDefaultFillValuePart(
        $wrapper,
        $delta,
        'value',
        $value,
      );

      $this->dateRangeFieldWidgetDefaultFillValuePart(
        $wrapper,
        $delta,
        'end_value',
        $value,
      );

      // @todo Support multi-value field.
      break;
    }

    return $this;
  }

  /**
   * @param \Behat\Mink\Element\NodeElement $wrapper
   * @param string $part
   *   Allowed values: value|end_value.
   * @param array $value
   *   - value.
   *   - end_value.
   *
   * @return $this
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function dateRangeFieldWidgetDefaultFillValuePart(
    NodeElement $wrapper,
    int $delta,
    string $part,
    array $value,
  ) {
    $selector = 'css';

    $locator = "*[name\$=\"[$delta][$part][date]\"]";
    $date_element = $wrapper->find($selector, $locator);
    $date = substr((string) $value[$part], 0, 10);
    if (!$date_element) {
      throw new ElementNotFoundException(
        $this->getSession(),
        NULL,
        $selector,
        $locator,
      );
    }
    $date_element->setValue($date);

    $locator = "*[name\$=\"[$delta][$part][time]\"]";
    $time_element = $wrapper->find($selector, $locator);
    $time = substr((string) $value[$part], 11);
    if (!$time_element && $time === '00:00:00') {
      return $this;
    }

    if (!$time_element) {
      throw new ElementNotFoundException(
        $this->getSession(),
        NULL,
        $selector,
        $locator,
      );
    }
    $time_element->setValue($time);

    return $this;
  }

}
