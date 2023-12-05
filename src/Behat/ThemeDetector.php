<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat;

use Behat\Mink\Session;

class ThemeDetector implements ThemeDetectorInterface {

  protected Session $session;

  /**
   * @todo The current detection method is not bulletproof.
   */
  public function getCurrentThemeName(Session $session): string {
    $this->session = $session;

    $themeName = $this->getCurrentThemeNameByElementAttribute();
    if (!$themeName) {
      $themeName = $this->getCurrentThemeNameByAjaxPageState();
    }

    return $themeName ?: 'olivero';
  }

  protected function getCurrentThemeNameByElementAttribute(): string {
    $page = $this->session->getPage();

    $xpathQueries = [
      '/head/link[@rel="shortcut icon"][@href]' => 'href',
      '/head/link[@rel="icon"][@href]' => 'href',
      '/head/meta[@name="msapplication-TileImage"][@content]' => 'content',
      '//a[@href="/"]/img[contains(@src, "/logo.svg")]' => 'src',
    ];
    foreach ($xpathQueries as $xpathQuery => $attributeName) {
      $element = $page->find('xpath', $xpathQuery);
      if (!$element) {
        continue;
      }

      $href = $element->getAttribute($attributeName);
      if (str_ends_with($href, '/core/misc/favicon.ico')
        || preg_match('@/sites/[^/]+/files/@', $href) === 1
      ) {
        // Drupal default favicon is used.
        // Custom favicon file is uploaded into the "files" directory.
        continue;
      }

      // This method assumes that the image URL is ".../themes/THEME_MACHINE_NAME/favicon.ico".
      $hrefParts = explode('/', trim($href, '/'));
      array_pop($hrefParts);

      return (string) end($hrefParts);
    }

    return '';
  }

  protected function getCurrentThemeNameByAjaxPageState(): string {
    $js = <<< JS
if (typeof drupalSettings == 'undefined'
  || !drupalSettings.hasOwnProperty('ajaxPageState')
  || !drupalSettings.ajaxPageState.hasOwnProperty('theme')
) {
    return '';
}

return drupalSettings.ajaxPageState.theme;
JS;

    return (string) $this->session->evaluateScript($js);
  }
}
