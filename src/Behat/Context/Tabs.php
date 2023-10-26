<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use PHPUnit\Framework\Assert;

class Tabs extends Base {

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    parent::initFinders();

    $this->finders += [
      'drupal.core.tabs.primary_tabs.wrapper' => [
        'selector' => 'css',
        'locator' => '.tabs.primary',
      ],
      'drupal.core.tabs.primary_tabs.wrapper__olivero' => [
        'selector' => 'css',
        'locator' => 'nav[role="navigation"].tabs-wrapper',
      ],
      'drupal.core.tabs.primary_tabs.wrapper__claro' => [
        'selector' => 'css',
        'locator' => 'nav[role="navigation"].tabs-wrapper',
      ],
      'drupal.core.tabs.primary_tabs.links' => [
        'selector' => 'css',
        'locator' => 'a',
      ],
      'drupal.core.tabs.primary_tabs.links__olivero' => [
        'selector' => 'css',
        'locator' => '.tabs__tab > a',
      ],
      'drupal.core.tabs.primary_tabs.links__claro' => [
        'selector' => 'css',
        'locator' => '.tabs__tab > a',
      ],
    ];

    return $this;
  }

  /**
   * @Then /^I should not see any primary tabs$/
   */
  public function assertPrimaryTabsWrapperNotExists() {
    Assert::assertNull($this->getPrimaryTabsWrapper(FALSE));
  }

  /**
   * @Then /^I should see the following primary tabs:$/
   *
   * @code
   * | View   |
   * | Edit   |
   * | Delete |
   * @endcode
   *
   * @todo Assert href and other link attributes.
   */
  public function assertPrimaryTabsTable(TableNode $table) {
    $expected = $table->getColumn(0);
    $isRequired = count($expected) > 0;

    $primaryTabsWrapper = $this->getPrimaryTabsWrapper(count($expected) > 0);
    if (!$isRequired && $primaryTabsWrapper === NULL) {
      // The ::assertPrimaryTabsNotExists() should be used instead.
      return;
    }

    $primaryTabLinks = $this->getPrimaryTabsLinks($primaryTabsWrapper);
    $actual = $this->getPrimaryTabsLinkLabels($primaryTabLinks);

    Assert::assertSame($expected, $actual);
  }

  /**
   * @When /^I click "(?P<linkText>[^"]+)" primary tab$/
   */
  public function doClickPrimaryTab(string $linkText) {
    $this
      ->getPrimaryTabsWrapper(TRUE)
      ->clickLink($linkText);
  }

  public function getPrimaryTabsWrapper(bool $required): ?NodeElement {
    $primaryTabsWrapperFinder = $this->getFinder('drupal.core.tabs.primary_tabs.wrapper');

    $primaryTabsWrapper = $this
      ->getSession()
      ->getPage()
      ->find($primaryTabsWrapperFinder['selector'], $primaryTabsWrapperFinder['locator']);

    if ($required && !$primaryTabsWrapper) {
      throw  new ElementNotFoundException(
        $this->getSession()->getDriver(),
        'other',
        $primaryTabsWrapperFinder['selector'],
        $primaryTabsWrapperFinder['locator']
      );
    }

    return $primaryTabsWrapper;
  }

  /**
   * @return \Behat\Mink\Element\NodeElement[]
   */
  public function getPrimaryTabsLinks(?NodeElement $primaryTabsWrapper): array {
    if (!$primaryTabsWrapper) {
      return [];
    }

    $finder = $this->getFinder('drupal.core.tabs.primary_tabs.links');

    return $primaryTabsWrapper->findAll($finder['selector'], $finder['locator']);
  }

  /**
   * @param \Behat\Mink\Element\NodeElement[] $linkElements
   *
   * @return string[]
   */
  public function getPrimaryTabsLinkLabels(array $linkElements): array {
    $linkLabels = [];
    foreach ($linkElements as $linkElement) {
      $linkLabels[] = $linkElement->getText();
    }

    return $linkLabels;
  }

}
