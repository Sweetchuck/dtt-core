<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use PHPUnit\Framework\Assert;
use Sweetchuck\DrupalTestTraits\Core\Utils as TestTraitsUtils;

/**
 * @todo Support for hierarchical menus.
 */
class Menu extends Base {

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    parent::initFinders();

    $blockWrapperLocator = '//nav[@role="navigation"]/*[normalize-space(text()) = "{{ blockLabel }}"]/parent::*';

    $this->finders += [
      'drupal.core.menu.block_wrapper' => [
        'selector' => 'xpath',
        'locator' => $blockWrapperLocator,
      ],
      'drupal.core.menu.block_links' => [
        'selector' => 'xpath',
        'locator' => "$blockWrapperLocator/div[@class=\"content\"]//ul//a",
      ],
      'drupal.core.menu.block_links__olivero' => [
        'selector' => 'xpath',
        'locator' => "$blockWrapperLocator/ul[matches(@class, '(^| )menu( |$)')]/li/a",
      ],
    ];

    return $this;
  }

  /**
   * @Then /^I should see the following links in the "(?P<blockLabel>[^"]*)" menu block:$/
   */
  public function assertMenuLinksSameTable(string $blockLabel, TableNode $table) {
    $expectedLinkLabels = $table->getColumn(0);
    $actualLinkElements = $this->getMenuLinks($blockLabel);

    Assert::assertSameSize(
      $expectedLinkLabels,
      $actualLinkElements,
      sprintf(
        'Expected number of links is %d. Actual: %d',
        count($expectedLinkLabels),
        count($actualLinkElements)
      )
    );

    foreach ($actualLinkElements as $delta => $actualLinkElement) {
      Assert::assertSame(
        $expectedLinkLabels[$delta],
        $actualLinkElement->getHtml(),
        sprintf(
          'Expected link title is "%s". Actual: "%s"',
          $expectedLinkLabels[$delta],
          $actualLinkElement->getHtml()
        )
      );
    }
  }

  /**
   * @When /^I click "(?P<linkLocator>[^"]+)" in the "(?P<blockLabel>[^"]+)" menu block$/
   */
  public function doClickOnMenuItem(string $blockLabel, string $linkLocator) {
    $this
      ->findMenuBlockWrapper($blockLabel, TRUE)
      ->clickLink($linkLocator);
  }

  /**
   * @return NodeElement[]
   */
  public function getMenuLinks(string $blockLabel): array {
    $menuLinksFinder = $this->getFinder(
      'drupal.core.menu.block_links',
      [
        '{{ blockLabel }}' => TestTraitsUtils::escapeXpathValue($blockLabel),
      ]
    );

    return $this
      ->getSession()
      ->getPage()
      ->findAll($menuLinksFinder['selector'], $menuLinksFinder['locator']);
  }

  public function findMenuBlockWrapper(string $blockLabel, bool $required = FALSE): ?NodeElement {
    $menuBlockWrapperFinder = $this->getFinder(
      'drupal.core.menu.block_wrapper',
      [
        '{{ blockLabel }}' => TestTraitsUtils::escapeXpathValue($blockLabel),
      ]
    );

    $menuBlockWrapper = $this
      ->getSession()
      ->getPage()
      ->find($menuBlockWrapperFinder['selector'], $menuBlockWrapperFinder['locator']);

    if (!$menuBlockWrapper && $required) {
      throw new ElementNotFoundException(
        $this->getSession()->getDriver(),
        null,
        $menuBlockWrapperFinder['selector'],
        $menuBlockWrapperFinder['locator'],
      );
    }

    return $menuBlockWrapper;
  }

}
