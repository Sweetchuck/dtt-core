<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core;

use Behat\Mink\Element\NodeElement;

class Utils {

  public static function escapeXpathValue(string $value): string {
    // @todo Somewhere there is a better solution for this.
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', FALSE);
  }

  /**
   * @param \Behat\Mink\Element\NodeElement[] $nodeElements
   *
   * @return string[]
   */
  public static function nodeElementsToText(array $nodeElements): array {
    $return = [];

    foreach ($nodeElements as $key => $nodeElement) {
      $return[$key] = trim($nodeElement->getText());
    }

    return $return;
  }

  /**
   * @param \Behat\Mink\Element\NodeElement[] $nodeElements
   *
   * @return string[]
   */
  public static function nodeElementsToHtml(array $nodeElements): array {
    $return = [];
    foreach ($nodeElements as $key => $nodeElement) {
      $return[$key] = $nodeElement->getHtml();
    }

    return $return;
  }

  public static function getFieldNameFromFieldWidgetWrapper(NodeElement $wrapper): ?string {
    return static::getFieldNameFromFieldWidgetWrapperClass($wrapper->getAttribute('class')) ?:
      static::getFieldNameFromFieldWidgetWrapperDataDrupalSelector($wrapper->getAttribute('data-drupal-selector'));
  }

  public static function getFieldNameFromFieldWidgetWrapperClass(?string $value): ?string {
    if ($value === NULL) {
      return NULL;
    }

    $matches = [];
    preg_match('/(?<=\\bfield--name-)[^\s]+(?=\\b)/', $value, $matches);

    return isset($matches[0]) ? str_replace('-', '_', $matches[0]) : NULL;
  }

  public static function getFieldNameFromFieldWidgetWrapperDataDrupalSelector(?string $value): ?string {
    if ($value === NULL) {
      return NULL;
    }

    return preg_replace(
      [
        '/^edit-/',
        '/-wrapper$/',
        '/-/',
      ],
      [
        '',
        '',
        '_',
      ],
      $value,
    );
  }

}
