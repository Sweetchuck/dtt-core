<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Behat\Mink\Element\NodeElement;

class Form extends Base {

  /**
   * @Given required attributes are removed from all input elements in form :formDrupalSelector
   *
   * @todo Maybe the "id" attribute of the form should be used as selector.
   */
  public function doRequiredAttributeRemoveAllByFormSelector(string $formDrupalSelector) {
    $form = $this->findFormByDrupalSelector($formDrupalSelector, TRUE);
    $this->doRequiredAttributeRemoveAllByForm($form);
  }

  public function doRequiredAttributeRemoveAllByForm(NodeElement $form) {
    $inputs = $form->findAll('xpath', '//input[@required] | //textarea[@required] | //select[@required]');
    $this->doRequiredAttributeRemoveAllByInputs($inputs);
  }

  /**
   * @param \Behat\Mink\Element\NodeElement[] $inputs
   */
  public function doRequiredAttributeRemoveAllByInputs(array $inputs) {
    $script = '';
    foreach ($inputs as $input) {
      $inputIdSafe = addslashes($input->getAttribute('id'));
      $script .= <<< JS
e = document.getElementById('$inputIdSafe');
e.removeAttribute('required');
e.removeAttribute('aria-required');

JS;
    }

    if ($script) {
      $script = "var e = null;\n" . $script;
      $this
        ->getSession()
        ->getDriver()
        ->executeScript($script);
    }
  }

}
