<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\MinkContext;

class Dialog extends Base {

  /**
   * {@inheritdoc}
   */
  protected function initFinders() {
    parent::initFinders();
    $this->initFindersDrupalCoreDialog();

    return $this;
  }

  /**
   * @return $this
   */
  protected function initFindersDrupalCoreDialog() {
    $this->finders += [
      'drupal.core.dialog.wrapper' => [
        'selector' => 'xpath',
        'locator' => '//div[@role="dialog"][//span[normalize-space(text()) = "{{ dialogTitle }}"]]',
      ],
      'drupal.core.dialog.action_buttons' => [
        'selector' => 'css',
        'locator' => '.ui-dialog-buttonset button',
      ],
    ];

    return $this;
  }

  /**
   * @When I press the :button action button in the :title dialog
   */
  public function doActionButtonPress(string $button, string $title) {
    $wrapper = $this->getWrapperByTitle($title);
    $buttons = $this->getActionButtons($wrapper);
    if (!isset($buttons[$button])) {
      throw new ExpectationException(
        sprintf(
          'Dialog action button with text "%s" not found. Available buttons: %s',
          $button,
          implode(', ', array_keys($buttons)),
        ),
        $this->getSession(),
      );
    }

    $buttons[$button]->press();

    $minkContext = $this->getContext(MinkContext::class);
    $minkContext->iWaitForAjaxToFinish();
  }

  public function getWrapperByTitle(string $title, bool $required = TRUE): ?NodeElement {
    $wrapperFinder = $this->getFinder(
      'drupal.core.dialog.wrapper',
      [
        // @todo Escape.
        '{{ dialogTitle }}' => $title,
      ],
    );

    $session = $this->getSession();
    $page = $session->getPage();
    $wrapper = $page->find($wrapperFinder['selector'], $wrapperFinder['locator']);
    if ($required && !$wrapper) {
      throw new ElementNotFoundException(
        $session,
        NULL,
        $wrapperFinder['selector'],
        $wrapperFinder['locator'],
      );
    }

    return $wrapper;
  }

  /**
   * @return \Behat\Mink\Element\NodeElement[]
   */
  public function getActionButtons(NodeElement $wrapper): array {
    $actionButtonsFinder = $this->getFinder('drupal.core.dialog.action_buttons');
    $buttons = $wrapper->findAll($actionButtonsFinder['selector'], $actionButtonsFinder['locator']);

    $return = [];
    foreach ($buttons as $button) {
      // @todo What if the text isn't plain text (image, icons etc...)?
      $return[$button->getText()] = $button;
    }

    return $return;
  }

}
