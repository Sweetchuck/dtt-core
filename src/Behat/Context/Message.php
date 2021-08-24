<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Sweetchuck\DrupalTestTraits\Core\System\MessageTrait;

class Message extends Base {

  use MessageTrait;

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    parent::initFinders();
    $this->initFindersDrupalCoreSystemMessage();

    return $this;
  }

  /**
   * @Then I should see :amount :messageType message(s)
   */
  public function assertMessagesAmountByType(string $messageType, string $amount) {
    $this->assertDrupalCoreSystemMessagesAmountByType($messageType, intval($amount));
  }

  /**
   * @Then I should see :amount message(s)
   */
  public function assertMessagesAmountTotal(string $amount) {
    $this->assertDrupalCoreSystemMessagesAmountTotal(intval($amount));
  }

  /**
   * @Then I should see the following :messageType message :message
   */
  public function assertMessage(string $messageType, string $message) {
    $this->assertDrupalCoreSystemMessage($messageType, $message);
  }

  /**
   * @Then I should not see the following :messageType message :message
   */
  public function assertMessageNot(string $messageType, string $message) {
    $this->assertDrupalCoreSystemMessageNot($messageType, $message);
  }

  /**
   * @Then I should see a(n) :messageType message like this :format
   */
  public function assertMessageMatchesFormat(string $messageType, string $format) {
    $this->assertDrupalCoreSystemMessageMatchesFormat($messageType, $format);
  }

  /**
   * @Then I should not see a(n) :messageType message like this :format
   */
  public function assertMessageNotMatchesFormat(string $messageType, string $format) {
    $this->assertDrupalCoreSystemMessageNotMatchesFormat($messageType, $format);
  }

  /**
   * @Then I should see only the following :messageType message(s):
   */
  public function assertMessagesByTypeSame(string $messageType, TableNode $tableNode) {
    $this->assertDrupalCoreSystemMessagesByTypeSame($messageType, $tableNode->getColumn(0));
  }

  /**
   * @Then I should not see any messages
   */
  public function assertMessagesEmpty() {
    $this->assertDrupalCoreSystemMessagesEmpty();
  }

  /**
   * @Then I should not see any :messageType messages
   */
  public function assertMessagesEmptyByType(string $messageType) {
    $this->assertDrupalCoreSystemMessagesEmptyByType($messageType);
  }

}
