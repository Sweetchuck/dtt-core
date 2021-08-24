<?php

namespace Sweetchuck\DrupalTestTraits\Core\Behat;

use Behat\Mink\Session;

interface ThemeDetectorInterface {
  public function getCurrentThemeName(Session $session): string;
}
