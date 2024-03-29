<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use PHPUnit\Framework\Assert;

/**
 * @todo Support for multiple "checkboxes" with the same label.
 */
class FormCheckboxes extends Base {

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    parent::initFinders();

    $this->finders += [
      'drupal.core.checkboxes.container' => [
        'selector' => 'css',
        'locator' => '.form-boolean-group.form-checkboxes',
      ],
      // Relative to the ::container.
      'drupal.core.checkboxes.wrapper' => [
        'selector' => 'xpath',
        'locator' => '//ancestor::fieldset[legend[normalize-space() = "{{ legend }}"]]',
      ],
      'drupal.core.checkboxes.legend' => [
        'selector' => 'xpath',
        'locator' => '//legend',
      ],
      // Relative to the ::wrapper.
      'drupal.core.checkboxes.label' => [
        'selector' => 'xpath',
        'locator' => '//label',
      ],
    ];

    return $this;
  }

  /**
   * @Then /^the "(?P<groupLabel>[^"]+)" checkboxes group has the following checkboxes:$/
   */
  public function assertCheckboxesSameTable(TableNode $table, string $groupLabel) {
    $expected = $table->getColumn(0);
    $actual = $this->getCheckboxLabels($this->getCheckboxesWrapper($groupLabel, true));
    Assert::assertSame($expected, $actual);
  }

  /**
   * @Then /^the "(?P<groupLabel>[^"]+)" checkboxes group contains the following checkboxes:$/
   */
  public function assertCheckboxesContainsTable(TableNode $table, string $groupLabel) {
    $expected = $table->getColumn(0);
    $actual = $this->getCheckboxLabels($this->getCheckboxesWrapper($groupLabel, true));
    Assert::assertSame($expected, array_intersect($expected, $actual));
  }

  /**
   * @Then /^the "(?P<groupLabel>[^"]+)" checkboxes group does not contain any of the following checkboxes:$/
   */
  public function assertCheckboxesNotContainsTable(TableNode $table, string $groupLabel) {
    $expected = $table->getColumn(0);
    $actual = $this->getCheckboxLabels($this->getCheckboxesWrapper($groupLabel, true));
    Assert::assertEmpty(array_intersect($expected, $actual));
  }

  /**
   * @Then /^the state of the checkboxes in the "(?P<groupLabel>[^"]+)" checkboxes group is the following:$/
   */
  public function assertCheckboxesStateTable(TableNode $table, string $groupLabel) {
    $expected = $this->parseStatesFromTable($table);
    $actual = $this->getCheckboxStates($this->getCheckboxesWrapper($groupLabel, true));
    Assert::assertSame($expected, $actual);
  }

  /**
   * @When I check the following checkboxes in the :groupLabel checkbox group:
   */
  public function doCheck(string $groupLabel, TableNode $table) {
    $wrapper = $this->getCheckboxesWrapper($groupLabel, TRUE);
    $containerFinder = $this->getFinder('drupal.core.checkboxes.container');
    $container = $wrapper->find($containerFinder['selector'], $containerFinder['locator']);
    $actual = $this->getCheckboxStates($wrapper);
    foreach ($table->getColumn(0) as $label) {
      // @todo This check is unnecessary, because ::checkField() also throws an exception if the checkbox couldn't be found.
      if (!array_key_exists($label, $actual)) {
        throw new ExpectationException(
          sprintf(
            'In checkbox group %s checkbox with label %s is not exists. Available checkboxes: %s',
            $groupLabel,
            $label,
            implode(', ', array_keys($actual)),
          ),
          $this->getSession()->getDriver(),
        );
      }

      if (!empty($actual[$label])) {
        // @todo Maybe a trigger_error() or assert() would be enough.
        throw new ExpectationException(
          sprintf('In checkbox group %s the %s checkbox already checked.', $groupLabel, $label),
          $this->getSession()->getDriver(),
        );
      }

      $container->checkField($label);
    }
  }

  public function parseStatesFromTable(TableNode $table): array {
    $states = [];
    foreach ($table->getRows() as $row) {
      $states[$row[0]] = $row[1] ?: null;
    }

    return $states;
  }

  public function getCheckboxesWrapper(string $groupLabel, bool $required = FALSE): ?NodeElement {
    $containers = $this->getCheckboxesContainers($required);
    $wrapperFinder = $this->getFinder(
      'drupal.core.checkboxes.wrapper',
      [
        '{{ legend }}' => $groupLabel,
      ],
    );

    if (!$containers && $required) {
      throw new ExpectationException(
        sprintf('There are no any checkboxes container. Label: "%s"', $groupLabel),
        $this->getSession(),
      );
    }

    foreach ($containers as $container) {
      $wrapper = $container->find($wrapperFinder['selector'], $wrapperFinder['locator']);
      if ($wrapper) {
        return $wrapper;
      }
    }

    if ($required) {
      throw new ExpectationException(
        sprintf('There is no checkboxes with label: "%s"', $groupLabel),
        $this->getSession(),
      );
    }

    return NULL;
  }

  /**
   * @return \Behat\Mink\Element\NodeElement[]
   */
  public function getCheckboxesContainers(bool $required = FALSE): array {
    $finder = $this->getFinder('drupal.core.checkboxes.container');

    $containers = $this
      ->getSession()
      ->getPage()
      ->findAll($finder['selector'], $finder['locator']);

    if (!$containers && $required) {
      throw new ElementNotFoundException(
        $this->getSession()->getDriver(),
        NULL,
        $finder['selector'],
        $finder['locator'],
      );
    }

    return $containers;
  }

  public function getCheckboxLabels(NodeElement $checkboxesWrapper): array {
    $checkboxLabelElements = $this->getCheckboxLabelElements($checkboxesWrapper);
    $checkboxLabels = [];
    foreach ($checkboxLabelElements as $checkboxLabelElement) {
      $checkboxLabels[] = $checkboxLabelElement->getText();
    }

    return $checkboxLabels;
  }

  public function getCheckboxStates(NodeElement $checkboxesWrapper): array {
    $checkboxLabelElements = $this->getCheckboxLabelElements($checkboxesWrapper);
    $checkboxStates = [];
    foreach ($checkboxLabelElements as $checkboxLabelElement) {
      $label = $checkboxLabelElement->getText();
      $checkbox = $checkboxesWrapper->findById($checkboxLabelElement->getAttribute('for'));
      $checkboxStates[$label] = $checkbox->getValue();
    }

    return $checkboxStates;
  }

  /**
   * @return \Behat\Mink\Element\NodeElement[]
   */
  public function getCheckboxLabelElements(NodeElement $checkboxesWrapper): array {
    $labelFinder = $this->getFinder('drupal.core.checkboxes.label');

    return $checkboxesWrapper->findAll(
      $labelFinder['selector'],
      $labelFinder['locator']
    );
  }

}
