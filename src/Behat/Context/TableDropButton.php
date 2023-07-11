<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

class TableDropButton extends Base {

  /**
   * @When I click on the :actionLabel drop button action in the :tableId table in the row where the :headerContent cell is :cellContent
   *
   * @code
   * I click on the "List terms" drop button action in the "taxonomy" table in the row where the "Vocabulary name" cell is "Tags"
   * @endcode
   *
   * @throws \Exception
   *
   * @todo This does not specify that in which column the DropButton should be.
   */
  public function doClickDropButtonAction(
    string $tableId,
    string $headerContent,
    string $cellContent,
    string $actionLabel,
  ) {
    /** @var \Sweetchuck\DrupalTestTraits\Core\Behat\Context\Table $tableContext */
    $tableContext = $this->getContext(Table::class);
    /** @var \Sweetchuck\DrupalTestTraits\Core\Behat\Context\DropButton $dropButtonContext */
    $dropButtonContext = $this->getContext(DropButton::class);

    $tableElement = $tableContext->findTableById($tableId, TRUE);
    $columnIndex = $tableContext->getColumnIndexByHeader($tableElement, $headerContent, TRUE);
    $rowElement = $tableContext->findRowByCellContent($tableElement, $columnIndex, $cellContent, TRUE);
    $dropButtonWrapper = $dropButtonContext->findDropButtonWrapper($rowElement, TRUE);
    $dropButtonWrapper->clickLink($actionLabel);
  }

}
