<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use PHPUnit\Framework\Assert;
use Sweetchuck\DrupalTestTraits\Core\System\LocalActionTrait;

class LocalAction extends Base {

  use LocalActionTrait;

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    parent::initFinders();
    $this->initFindersDrupalCoreSystemLocalAction();

    return $this;
  }

  /**
   * @Then I should see :amount local action(s)
   */
  public function assertLocalActionsCount(string $amount) {
    $this->assertDrupalCoreSystemLocalActionsCount(intval($amount));
  }

  /**
   * @When I click on the :label local action
   *
   * @code
   * When I click on the "Add content" local action
   * @endcode
   */
  public function doLocalActionClick(string $label) {
    $link = $this->getDrupalCoreSystemLocalActionByLabel($label);
    Assert::assertNotEmpty(
      $link,
      "Local action with '$label' could not be found",
    );

    $link->click();
  }
}
