<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Behat\Context\TranslatableContext;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawDrupalContext;
use Sweetchuck\DrupalTestTraits\Core\Behat\TranslatableContextTrait;
use Sweetchuck\DrupalTestTraits\Core\FinderTrait;
use Sweetchuck\DrupalTestTraits\Core\Utils as TestTraitsUtils;

class Base extends RawDrupalContext implements TranslatableContext {

  use FinderTrait;
  use TranslatableContextTrait;

  protected function getFinderSettings(): array {
    return $this->getDrupalParameter('selectors');
  }

  public function __construct() {
    $this->initFinders();
  }

  public function findFormByDrupalSelector(string $drupalSelector, bool $required = FALSE): ?NodeElement {
    $selector = 'xpath';
    $locator = sprintf('//form[@data-drupal-selector = "%s"]', TestTraitsUtils::escapeXpathValue($drupalSelector));
    $session = $this->getSession();
    $form = $session->getPage()->find($selector, $locator);
    if (!$form && $required) {
      throw new ElementNotFoundException(
        $session->getDriver(),
        NULL,
        $selector,
        $locator,
      );
    }

    return $form;
  }

}
