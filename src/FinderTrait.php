<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core;

use PHPUnit\Framework\Assert;
use Sweetchuck\DrupalTestTraits\Core\Behat\ThemeDetector;
use Sweetchuck\DrupalTestTraits\Core\Behat\ThemeDetectorInterface;

trait FinderTrait {

  /**
   * @return \Behat\Mink\Session
   */
  abstract protected function getSession();

  /**
   * @return \Symfony\Component\DependencyInjection\ContainerInterface|\Symfony\Component\DependencyInjection\ContainerBuilder|\Psr\Container\ContainerInterface
   */
  abstract public function getContainer();

  protected array $finders = [];

  protected array $themeParents = [];

  protected function getParentThemes(string $themeName): array {
    if (!array_key_exists($themeName, $this->themeParents)) {
      $themeHandler = \Drupal::getContainer()->get('theme_handler');
      $baseThemes = $themeHandler->getBaseThemes($themeHandler->listInfo(), $themeName);
      $this->themeParents[$themeName] = array_keys($baseThemes);
    }

    return $this->themeParents[$themeName];
  }

  protected ?ThemeDetectorInterface $themeDetector = NULL;

  protected function getThemeDetector(): ThemeDetectorInterface {
    if ($this->themeDetector === NULL) {
      $serviceId = 'sweetchuck.behat.theme_detector';
      $this->themeDetector = $this->getContainer()->has($serviceId) ?
        $this->themeDetector = $this->getContainer()->get($serviceId)
        : new ThemeDetector();
    }

    return $this->themeDetector;
  }

  /**
   * @return $this
   */
  protected function initFinders() {
    return $this;
  }

  protected function isFinderExists(string $id): bool {
    return array_key_exists($id, $this->finders) || array_key_exists($id, $this->getFinderSettings());
  }

  /**
   * @see \Drupal\DrupalExtension\DrupalParametersTrait::getDrupalParameter
   */
  abstract protected function getFinderSettings(): array;

  protected function getFinder(string $finderName, array $args = []): array {
    $drupalSelectors = $this->getFinderSettings();
    $finderNameSuggestions = $this->getFinderNameSuggestions($finderName);
    $finder = NULL;
    foreach ($finderNameSuggestions as $finderName) {
      if (!empty($drupalSelectors[$finderName])) {
        $finder = $drupalSelectors[$finderName];

        break;
      }

      if (!empty($this->finders[$finderName])) {
        $finder = $this->finders[$finderName];

        break;
      }
    }

    Assert::assertNotEmpty(
      $finder,
      sprintf('No such selector configured: "%s"', $finderName)
    );

    $finder = $this->normalizeFinder($finder);
    if ($args) {
      $finder['locator'] = strtr($finder['locator'], $args);
    }

    return $finder;
  }

  /**
   * @return string[]
   */
  protected function getFinderNameSuggestions(string $finderName): array {
    $currentThemeName = $this
      ->getThemeDetector()
      ->getCurrentThemeName($this->getSession());

    $suggestions = [
      "{$finderName}__{$currentThemeName}",
    ];

    foreach ($this->getParentThemes($currentThemeName) as $parentTheme) {
      $suggestions[] = "{$finderName}__{$parentTheme}";
    }

    $suggestions[] = $finderName;

    return $suggestions;
  }

  /**
   * @param array|string $finder
   */
  protected function normalizeFinder($finder): array {
    if (!is_array($finder)) {
      $matches = [];
      $pattern = '/^(?P<selector>(xpath|css)): /u';
      preg_match($pattern, $finder, $matches);
      if ($matches) {
        return [
          'selector' => $matches['selector'],
          'locator' => preg_replace($pattern, '', $finder),
        ];
      }

      $finder = [
        'locator' => $finder,
      ];
    }

    return $finder + ['selector' => 'css'];
  }

}
