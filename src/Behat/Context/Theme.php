<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use PHPUnit\Framework\Assert;

class Theme extends Base {

  /**
   * @Then the current theme is :themeName
   */
  public function assertCurrentThemeName(string $themeName): void {
    $themeDetector = $this->getThemeDetector();

    Assert::assertSame(
      $themeName,
      $themeDetector->getCurrentThemeName($this->getSession()),
    );
  }

}
