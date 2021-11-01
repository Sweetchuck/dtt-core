<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\PyStringNode;
use PHPUnit\Framework\Assert;

class Ckeditor extends Base {

  /**
   * @Given the :locator wysiwyg field is filled with :value
   * @When I fill in wysiwyg on field :locator with :value
   */
  public function doFillInWysiwygOnFieldWith(string $locator, string $value) {
    $element = $this
      ->getSession()
      ->getPage()
      ->findField($locator);

    Assert::assertNotEmpty(
      $element,
      "Could not find WYSIWYG with locator: '$locator'"
    );

    $fieldId = $element->getAttribute('id');
    Assert::assertNotEmpty(
      $fieldId,
      "Could not find an ID for field with locator: '$locator'"
    );

    $this->ckeditorSetData($fieldId, $value);
  }

  /**
   * @Then I fill in wysiwyg on field :locator with:
   */
  public function doFillInWysiwygOnFieldWithLong(string $locator, PyStringNode $value) {
    $this->doFillInWysiwygOnFieldWith($locator, $value->getRaw());
  }

  /**
   * @return $this
   */
  public function ckeditorSetData(string $fieldId, string $newValue) {
    $fieldIdSafe = addslashes($fieldId);
    $newValueSafe = addslashes($newValue);

    $this
      ->getSession()
      ->executeScript("console.log('{$fieldIdSafe}') ; CKEDITOR.instances['{$fieldIdSafe}'].setData('{$newValueSafe}');");

    return $this;
  }

}
