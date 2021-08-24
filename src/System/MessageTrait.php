<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\System;

use PHPUnit\Framework\Assert;
use Sweetchuck\DrupalTestTraits\Core\Assert as A;
use Sweetchuck\DrupalTestTraits\Core\Utils as TestTraitsUtils;

trait MessageTrait {

  /**
   * @return \Behat\Mink\Session
   *
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  abstract protected function getSession();

  /**
   * @abstract
   *
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  protected array $finders = [];

  /**
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  abstract protected function getFinderSettings(): array;

  /**
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  abstract protected function getFinder(string $finderName, array $args = []): array;

  /**
   * @see \Sweetchuck\DrupalTestTraits\Core\FinderTrait
   */
  abstract protected function isFinderExists(string $finderName): bool;

  /**
   * @var string[]
   */
  protected array $drupalCoreSystemMessageTypes = [];

  /**
   * @return $this
   */
  protected function initFindersDrupalCoreSystemMessage() {
    // @todo Define selectors for other core themes as well.
    $oliveroPrefix = '*[data-drupal-messages] .messages.messages';
    $oliveroSuffix = '.messages__list > .messages__item';

    $this->finders += [
      'drupal.core.message.single' => [
        'selector' => 'css',
        'locator' => $oliveroPrefix . '--{{ messageType }} > *[role="alert"]',
      ],
      'drupal.core.message.single.status' => [
        'selector' => 'css',
        'locator' => $oliveroPrefix . '--{{ messageType }} > *[role="alert"]',
      ],
      'drupal.core.message.single.warning' => [
        'selector' => 'css',
        'locator' => $oliveroPrefix . '--{{ messageType }} > *[role="alert"]',
      ],
      'drupal.core.message.single.error' => [
        'selector' => 'css',
        'locator' => $oliveroPrefix . '--{{ messageType }} > *[role="alert"]',
      ],
      'drupal.core.message.multiple' => [
        'selector' => 'css',
        'locator' => $oliveroPrefix . '--{{ messageType }} > *[role="alert"] ' . $oliveroSuffix,
      ],
      'drupal.core.message.multiple.status' => [
        'selector' => 'css',
        'locator' => $oliveroPrefix . '--{{ messageType }} > *[role="alert"] ' . $oliveroSuffix,
      ],
      'drupal.core.message.multiple.warning' => [
        'selector' => 'css',
        'locator' => $oliveroPrefix . '--{{ messageType }} > *[role="alert"] ' . $oliveroSuffix,
      ],
      'drupal.core.message.multiple.error' => [
        'selector' => 'css',
        'locator' => $oliveroPrefix . '--{{ messageType }} > *[role="alert"] ' . $oliveroSuffix,
      ],
    ];

    foreach (['status', 'warning', 'error'] as $type) {
      $this->finders += [
        "drupal.core.message.single.{$type}__olivero" => [
          'selector' => 'css',
          'locator' => $oliveroPrefix . "--$type > .messages__container > .messages__content",
        ],
        "drupal.core.message.multiple.{$type}__olivero" => [
          'selector' => 'css',
          'locator' => $oliveroPrefix . "--$type > *[role=\"alert\"] " . $oliveroSuffix,
        ],
      ];
    }

    return $this;
  }

  public function assertDrupalCoreSystemMessagesAmountByType(string $messageType, int $amount) {
    Assert::assertSame($amount, count($this->getActualDrupalCoreSystemMessagesByType($messageType)));
  }

  public function assertDrupalCoreSystemMessagesAmountTotal(int $amount) {
    $numOfMessages = 0;
    foreach ($this->getActualDrupalCoreSystemMessagesGroupByType() as $type => $messages) {
      $numOfMessages += count($messages);
    }

    Assert::assertSame($amount, $numOfMessages);
  }

  public function assertDrupalCoreSystemMessage(string $messageType, string $message) {
    Assert::assertContains(
      $message,
      TestTraitsUtils::nodeElementsToText($this->getActualDrupalCoreSystemMessagesByType($messageType))
    );
  }

  public function assertDrupalCoreSystemMessageNot(string $messageType, string $message) {
    Assert::assertNotContains(
      $message,
      TestTraitsUtils::nodeElementsToText($this->getActualDrupalCoreSystemMessagesByType($messageType))
    );
  }

  public function assertDrupalCoreSystemMessageMatchesFormat(string $messageType, string $format) {
    A::assertOneOfStringsMatchesFormat(
      $format,
      TestTraitsUtils::nodeElementsToText($this->getActualDrupalCoreSystemMessagesByType($messageType))
    );
  }

  public function assertDrupalCoreSystemMessageNotMatchesFormat(string $messageType, string $format) {
    A::assertNonOfStringsMatchesFormat(
      $format,
      TestTraitsUtils::nodeElementsToText($this->getActualDrupalCoreSystemMessagesByType($messageType))
    );
  }

  public function assertDrupalCoreSystemMessagesByTypeSame(string $messageType, array $messages) {
    Assert::assertSame(
      $messages,
      TestTraitsUtils::nodeElementsToText($this->getActualDrupalCoreSystemMessagesByType($messageType))
    );
  }

  public function assertDrupalCoreSystemMessagesEmpty() {
    $messagesByType = $this->getActualDrupalCoreSystemMessagesGroupByType();
    foreach ($messagesByType as $messageType => $messages) {
      Assert::assertEmpty(
        $messages,
        "There is no any '$messageType' message"
      );
    }
  }

  public function assertDrupalCoreSystemMessagesEmptyByType(string $messageType) {
    Assert::assertEmpty(
      $this->getActualDrupalCoreSystemMessagesByType($messageType),
      "There is no any '$messageType' message"
    );
  }

  /**
   * @return string[]
   */
  public function getDrupalCoreSystemMessageTypes(): array {
    if (!$this->drupalCoreSystemMessageTypes) {
      $finderIds = array_unique(array_merge(
        array_keys($this->getFinderSettings()),
        array_keys($this->finders)
      ));

      foreach ($finderIds as $finderId) {
        $matches = [];
        if (preg_match('/^drupal\.core\.message\.single\.(?P<type>.+?)(__|$)/', $finderId, $matches)) {
          $this->drupalCoreSystemMessageTypes[] = $matches['type'];
        }
      }
    }

    return $this->drupalCoreSystemMessageTypes;
  }

  /**
   * @return \Behat\Mink\Element\NodeElement[]
   */
  public function getActualDrupalCoreSystemMessagesByType(string $type): array {
    $args = ['{{ messageType }}' => $type];

    $finder = $this->isFinderExists("drupal.core.message.multiple.$type") ?
      $this->getFinder("drupal.core.message.multiple.$type", $args)
      : $this->getFinder('drupal.core.message.multiple', $args);

    $items = $this
      ->getSession()
      ->getPage()
      ->findAll($finder['selector'], $finder['locator']);

    if (!$items) {
      $finder = $this->isFinderExists("drupal.core.message.single.$type") ?
        $this->getFinder("drupal.core.message.single.$type", $args)
        : $this->getFinder('drupal.core.message.single', $args);

      $items = $this
        ->getSession()
        ->getPage()
        ->findAll($finder['selector'], $finder['locator']);
    }

    return $items;
  }

  public function getActualDrupalCoreSystemMessagesGroupByType(array $types = []): array {
    $messageTypes = array_unique(array_merge($this->getDrupalCoreSystemMessageTypes(), $types));
    $messages = [];
    foreach ($messageTypes as $messageType) {
      $messages[$messageType] = $this->getActualDrupalCoreSystemMessagesByType($messageType);
    }

    return $messages;
  }

}
