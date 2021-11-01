<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\MinkContext;
use Sweetchuck\DrupalTestTraits\Core\Utils;

class Media extends Base {

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    parent::initFinders();
    $this->initFindersDrupalCoreMedia();

    return $this;
  }

  /**
   * @return $this
   */
  protected function initFindersDrupalCoreMedia() {
    $this->finders += [
      'drupal.core.media.field_widget.media_library_widget.wrappers' => [
        'selector' => 'css',
        'locator' => '.field--type-entity-reference.field--widget-media-library-widget',
      ],
      'drupal.core.media.field_widget.media_library_widget.label' => [
        'selector' => 'xpath',
        'locator' => './fieldset/legend/span[normalize-space(text()) = "{{ fieldLabel }}"]',
      ],
      'drupal.core.media.field_widget.media_library_widget.opener' => [
        'selector' => 'button',
        'locator' => '{{ fieldName }}-media-library-open-button',
      ],
    ];

    return $this;
  }

  /**
   * @When I open the media library browser of the :field media field
   */
  public function doMediaLibraryBrowserOpen(string $field) {
    $wrappers = $this->findAllFieldWidgetMediaLibraryWrappers();
    $wrapper = $this->findFieldWidgetMediaLibraryWrapperByFieldLabel($field, $wrappers);
    $openButton = $this->findFieldWidgetMediaLibraryAddMediaButton($wrapper);
    $openButton->press();
    $minkContext = $this->getContext(MinkContext::class);
    $minkContext->iWaitForAjaxToFinish();
  }

  /**
   * Collects all the widget wrappers which uses the "media_library_widget" widget.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   */
  public function findAllFieldWidgetMediaLibraryWrappers(?ElementInterface $root = NULL): array {
    if (!$root) {
      $root = $this->getSession()->getPage();
    }

    $finder = $this->getFinder('drupal.core.media.field_widget.media_library_widget.wrappers');

    return $root->findAll($finder['selector'], $finder['locator']);
  }

  public function findFieldWidgetMediaLibraryWrapperByFieldLabel(string $fieldLabel, array $wrappers): ?NodeElement {
    $finder = $this->getFinder(
      'drupal.core.media.field_widget.media_library_widget.label',
      [
        '{{ fieldLabel }}' => Utils::escapeXpathValue($fieldLabel),
      ],
    );

    foreach ($wrappers as $wrapper) {
      $element = $wrapper->find($finder['selector'], $finder['locator']);
      if ($element) {
        return $wrapper;
      }
    }

    return NULL;
  }

  public function findFieldWidgetMediaLibraryAddMediaButton(NodeElement $wrapper): ?NodeElement {
    $fieldName = Utils::getFieldNameFromFieldWidgetWrapper($wrapper);
    $finder = $this->getFinder(
      'drupal.core.media.field_widget.media_library_widget.opener',
      [
        '{{ fieldName }}' => $fieldName,
      ],
    );

    return $wrapper->findButton($finder['locator']);
  }

}
