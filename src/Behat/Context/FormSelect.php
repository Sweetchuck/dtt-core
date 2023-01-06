<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use PHPUnit\Framework\Assert;
use function PHPUnit\Framework\assertSame;

class FormSelect extends Base {

  /**
   * @Then /^"(?P<locator>[^"]+)" flat select list has the following options:$/
   *
   * @code
   * Then "Colors" flat select list has the following options:
   *   | label |
   *   | Red   |
   *   | Green |
   * @endcode
   *
   * @code
   * Then "Colors" flat select list has the following options:
   *   | value | label |
   *   | red   | Red   |
   *   | green | Green |
   * @endcode
   *
   * @code
   * Then "Colors" flat select list has the following options:
   *   | selected | value | label |
   *   |          | pink  | Pink  |
   *   |        * | red   | Red   |
   *   |          | blue  | Blue  |
   * @endcode
   */
  public function assertSelectFlatOptions(string $locator, TableNode $table) {
    $select = $this->getSession()->getPage()->findField($locator);
    if (!$select) {
      throw new ElementNotFoundException(
        $this->getSession()->getDriver(),
        NULL,
        'field',
        $locator,
      );
    }

    $actual_options = $this->collectOptions($select);
    $expected_options = $this->parseFlatOptionsTable($table);

    foreach ($expected_options as $key => $expected_option) {
      $msg_prefix = 'Select {locator}; index: {index}; label: {label};';
      $msg_args = [
        '{index}' => $key + 1,
        '{locator}' => $locator,
        '{label}' => implode(' / ', $expected_option['label']),
      ];

      Assert::assertArrayHasKey(
        $key,
        $actual_options,
        strtr("$msg_prefix is missing", $msg_args),
      );
      foreach (['selected', 'value', 'label'] as $property) {
        $msg_args['{property}'] = $property;
        if (isset($expected_option[$property])) {
          Assert::assertSame(
            $expected_option[$property],
            $actual_options[$key][$property],
            strtr("$msg_prefix property: {property}; does not match", $msg_args),
          );
        }
      }
    }

    Assert:assertSame(
      [],
      array_slice($actual_options, count($expected_options), NULL, TRUE),
    );
  }

  /**
   * @When /^I choose "(?P<options>[^"]+)" options? from the "(?P<locator>[^"]+)" select list$/
   *
   * @code
   * When I choose "Pink" option from the "Color" select list
   * @endcode
   *
   * @code
   * When I choose "Pink; Green" options from the "Colors" select list
   * @endcode
   */
  public function doCheckList(string $options, string $locator) {

  }

  /**
   * @When /^I choose the following options from the "(?P<locator>[^"]+)" select list:
   *
   * @code
   * I choose the following options from the "Colors" select list:
   *   | Red   |
   *   | Green |
   * @endcode
   */
  public function doCheckTable() {

  }

  public function collectOptions(NodeElement $parent, array $parent_labels = []): array {
    $options = [];
    $children = $parent->findAll('xpath', './option | ./optgroup');
    foreach ($children as $child) {
      switch ($child->getTagName()) {
        case 'option':
          $options[] = [
            'selected' => $child->hasAttribute('selected'),
            'value' => $child->getAttribute('value'),
            'label' => array_merge(
              $parent_labels,
              [$child->getText()],
            ),
          ];
          break;

        case 'optgroup':
          $options = array_merge(
            $options,
            $this->collectOptions(
              $child,
              array_merge(
                $parent_labels,
                [$child->getAttribute('label')],
              ),
            ),
          );
          break;
      }
    }

    return $options;
  }

  /**
   * @phpstan-return array<dtt-core-select-options>
   */
  public function parseFlatOptionsTable(TableNode $table): array {
    $options = [];
    foreach ($table->getColumnsHash() as $row) {
      $options[] = [
        'selected' => isset($row['selected']) ? (bool) $row['selected'] : NULL,
        'value' => $row['value'] ?? NULL,
        'label' => [
          $row['label'],
        ],
      ];
    }

    return $options;
  }

}
