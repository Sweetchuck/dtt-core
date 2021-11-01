<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\MinkContext;
use Sweetchuck\DrupalTestTraits\Core\Utils;

class Link extends Base {

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    return parent::initFinders()
      ->initFindersDrupalCoreLink();
  }

  protected function initFindersDrupalCoreLink() {
    $this->finders += [
      'drupal.core.link.field_widget.default.wrappers' => [
        'selector' => 'css',
        'locator' => '.field--type-link.field--widget-link-default',
      ],
      'drupal.core.link.field_widget.default.label' => [
        'selector' => 'xpath',
        'locator' => './/h4[normalize-space(text()) = "{{ fieldLabel }}"]',
      ],
    ];

    return $this;
  }

  /**
   * @When I fill :field link field with:
   *
   * @code
   * When I fill "My links 01" link field with:
   *   | uri                 | title       |
   *   | https://example.org | Example.org |
   *   | https://drupal.org  | Drupal.org  |
   * @endcode
   */
  public function doLinkFieldWidgetDefaultFill(string $field, TableNode $table) {
    $wrappers = $this->getLinkFieldWidgetDefaultWrappers();
    $wrapper = $this->findLinkFieldWidgetDefaultWrapperByFieldLabel($field, $wrappers);
    if (!$wrapper) {
      // @todo Find it by field machine-name.
      throw new ExpectationException('Link fieldWidget.default element not found', $this->getSession());
    }

    $values = $this->convertLinkTableNodeToFieldValues($table);
    $this->linkFieldWidgetDefaultFillValues($values, $wrapper);
  }

  /**
   * @When I press the "Add another item" button of the :field link field
   */
  public function doLinkFieldWidgetDefaultAddAnotherItem(string $field) {
    $wrappers = $this->getLinkFieldWidgetDefaultWrappers();
    $wrapper = $this->findLinkFieldWidgetDefaultWrapperByFieldLabel($field, $wrappers);
    $fieldName = Utils::getFieldNameFromFieldWidgetWrapperClass($wrapper->getAttribute('class'));
    $wrapper->pressButton("{$fieldName}_add_more");
  }

  public function convertLinkTableNodeToFieldValues(TableNode $tableNode): array {
    $values = [];
    foreach ($tableNode->getColumnsHash() as $row) {
      $values[] = $row;
    }

    return $values;
  }

  /**
   * @return \Behat\Mink\Element\NodeElement[]
   */
  public function getLinkFieldWidgetDefaultWrappers(?ElementInterface $root = NULL): array {
    if (!$root) {
      $root = $this->getSession()->getPage();
    }

    $finder = $this->getFinder('drupal.core.link.field_widget.default.wrappers');

    return $root->findAll($finder['selector'], $finder['locator']);
  }

  /**
   * @param string $fieldLabel
   * @param \Behat\Mink\Element\NodeElement[] $wrappers
   */
  public function findLinkFieldWidgetDefaultWrapperByFieldLabel(string $fieldLabel, array $wrappers): ?NodeElement {
    $finder = $this->getFinder(
      'drupal.core.link.field_widget.default.label',
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

  public function linkFieldWidgetDefaultFillValues(array $values, NodeElement $wrapper) {
    $minkContext = $this->getContext(MinkContext::class);
    $fieldName = Utils::getFieldNameFromFieldWidgetWrapperClass($wrapper->getAttribute('class'));
    $delta = -1;
    foreach ($values as $delta => $value) {
      $uriInput = $wrapper->find('css', "*[name\$=\"[$delta][uri]\"]");
      if (!$uriInput) {
        $wrapper->pressButton("{$fieldName}_add_more");
        $minkContext->iWaitForAjaxToFinish();
        $uriInput = $wrapper->find('css', "*[name\$=\"[$delta][uri]\"]");
      }

      $uriInput->setValue($value['uri']);

      if (isset($value['title'])) {
        $wrapper
          ->find('css', "*[name\$=\"[$delta][title]\"]")
          ->setValue($value['title']);
      }
    }

    $delta++;
    while ($uriInput = $wrapper->find('css', "*[name\$=\"[$delta][uri]\"]")) {
      $uriInput->setValue('');
      $titleInput = $wrapper->find('css', "*[name\$=\"[$delta][title]\"]");
      if ($titleInput) {
        $titleInput->setValue('');
      }

      $delta++;
    }

    return $this;
  }

}
