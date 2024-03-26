<?php

declare(strict_types=1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use PHPUnit\Framework\Assert;

class FormRadios extends Base {

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    parent::initFinders();

    $this->finders += [
      'drupal.core.radios.wrapper' => [
        'selector' => 'xpath',
        'locator' => '//ancestor::fieldset[legend[normalize-space() = "{{ legend }}"]]',
      ],
    ];

    return $this;
  }

  /**
   * @Then radios :label has the following options:
   *
   * @code
   * Then radios "Preview before submitting" has the following options:
   *   | value | label    |
   *   | 0     | Disabled |
   *   | 1     | Optional |
   *   | 2     | Required |
   * @endcode
   */
  public function assertRadiosOptionsSameTable(string $label, TableNode $expectedTable): void {
    $wrapper = $this->findRadiosWrapperByLabel($label);
    Assert::assertNotNull($wrapper, "There is no radios with label '$label'");

    $this->assertRadiosOptionsSame($expectedTable->getColumnsHash(), $wrapper);
  }

  public function assertRadiosOptionsSame(array $expectedOptions, NodeElement $wrapper): void {
    $first = reset($expectedOptions);
    $columns = $first ? array_keys($first) : ['value', 'label'];

    $actualOptions = [];
    foreach ($this->getRadiosOptions($wrapper) as $actualOption) {
      $item = array_fill_keys($columns, NULL);
      if (array_key_exists('value', $item)) {
        $item['value'] = $actualOption['value'];
      }

      if (array_key_exists('label', $item)) {
        $item['label'] = $actualOption['label'];
      }

      $actualOptions[] = $item;
    }

    Assert::assertSame(
      $expectedOptions,
      $actualOptions,
      // @todo Error message.
    );
  }

  public function findRadiosWrapperByLabel(string $label, ?NodeElement $parent = NULL): ?NodeElement {
    if (!$parent) {
      $parent = $this->getSession()->getPage();
    }

    $finder = $this->getFinder(
      'drupal.core.radios.wrapper',
      [
        '{{ legend }}' => $label,
      ],
    );

    return $parent->find($finder['selector'], $finder['locator']);
  }

  public function getRadiosOptions(NodeElement $wrapper): array {
    // @todo Define finder.
    $optionWrappers = $wrapper->findAll('css', '.form-type--radio');

    $options = [];
    foreach ($optionWrappers as $optionWrapper) {
      // @todo Define finder.
      $input = $optionWrapper->find('css', 'input[type="radio"]');
      $value = $input->getAttribute('value');
      // @todo Define finder.
      $label = $optionWrapper->find('css', 'label');
      $options[$value] = [
        'inputElement' => $input,
        'labelElement' => $label,
        'value' => $value,
        'label' => trim($label?->getText()),
      ];
    }

    return $options;
  }

}
