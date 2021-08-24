<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core;

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

}
