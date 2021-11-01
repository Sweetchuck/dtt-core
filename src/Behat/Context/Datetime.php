<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
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
   */
  public function doDateRangeFieldWidgetDefaultFill(string $field, TableNode $table) {
    $baseDatetime = new \DateTime();
    $wrappers = $this->getDateRangeFieldWidgetDefaultWrappers();
    $wrapper = $this->findDateRangeFieldWidgetDefaultWrapperByFieldLabel($field, $wrappers);
    if (!$wrapper) {
      // @todo Find it by field machine-name.
      throw new ExpectationException('Date range fieldWidget.default element not found', $this->getSession());
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

  public function dateRangeFieldWidgetDefaultFillValues(array $values, NodeElement $wrapper) {
    foreach ($values as $delta => $value) {
      $wrapper
        ->find('css', "*[name\$=\"[$delta][value][date]\"]")
        ->setValue(substr((string) $value['value'], 0, 10));
      $wrapper
        ->find('css', "*[name\$=\"[$delta][value][time]\"]")
        ->setValue(substr((string) $value['value'], 11));

      $wrapper
        ->find('css', "*[name\$=\"[$delta][end_value][date]\"]")
        ->setValue(substr((string) $value['end_value'], 0, 10));
      $wrapper
        ->find('css', "*[name\$=\"[$delta][end_value][time]\"]")
        ->setValue(substr((string) $value['end_value'], 11));

      // @todo Support multi-value field.
      break;
    }

    return $this;
  }

}
