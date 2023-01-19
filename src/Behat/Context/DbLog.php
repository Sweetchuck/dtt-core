<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat\Context;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use PHPUnit\Framework\Assert;

class DbLog extends Base {

  protected bool $dbLogEnabled = FALSE;

  protected array $logEntriesBefore;

  /**
   * @BeforeStep
   */
  public function storeWatchdogState(): void {
    $this->dbLogEnabled = \Drupal::getContainer()
      ->get('module_handler')
      ->moduleExists('dblog');

    if (!$this->dbLogEnabled) {
      return;
    }

    $this->logEntriesBefore = $this->getLogEntries();
  }

  /**
   * @AfterStep
   */
  public function checkWatchdogState(): void {
    if (!$this->dbLogEnabled) {
      return;
    }

    $messages = $this->checkLogEntries(
      $this->logEntriesBefore,
      $this->getLogEntries(),
    );

    if (!$messages) {
      return;
    }

    array_unshift($messages, 'Error messages in the watchdog');
    Assert::fail(implode("\n", $messages));
  }

  public function getLogEntries(): array {
    $database = \Drupal::getContainer()->get('database');
    $select = $database->select('watchdog', 'w');
    $select->fields('w', ['type', 'severity']);
    $select->addExpression('MAX(w.wid)', 'wid');
    $select->groupBy('w.type');
    $select->groupBy('w.severity');
    $select->orderBy('w.type');
    $select->orderBy('w.severity');

    $log_entries = [];
    foreach ($select->execute()->fetchAll(\PDO::FETCH_ASSOC) as $row) {
      $log_entries[$row['type']][$row['severity']] = (int) $row['wid'];
    }

    return $log_entries;
  }

  public function checkLogEntries(array $old, array $new): array {
    // @todo Make this configurable.
    $type_severities = [
      'php' => [
        RfcLogLevel::EMERGENCY,
        RfcLogLevel::ALERT,
        RfcLogLevel::CRITICAL,
        RfcLogLevel::ERROR,
        RfcLogLevel::WARNING,
      ],
    ];

    // @todo Use the official API to fetch and parse log entries.
    $messages = [];
    foreach ($type_severities as $type => $severities) {
      foreach ($severities as $severity) {
        settype($old[$type][$severity], 'int');
        settype($new[$type][$severity], 'int');
        if ($old[$type][$severity] >= $new[$type][$severity]) {
          continue;
        }

        $entries = $this->getNewLogEntries(
          $type,
          $severity,
          $old[$type][$severity],
        );

        foreach ($entries as $entry) {
          $messages[] = (string) (new FormattableMarkup($entry['message'], $entry['variables']));
        }
      }
    }

    return $messages;
  }

  public function getNewLogEntries(string $type, int $severity, int $id_low): array {
    $database = \Drupal::getContainer()->get('database');
    $select = $database->select('watchdog', 'w');
    $select->fields('w');
    $select->condition('w.type', $type);
    $select->condition('w.severity', $severity);
    $select->condition('w.wid', $id_low, '>');
    $select->orderBy('w.wid');

    $entries = [];
    foreach ($select->execute()->fetchAll(\PDO::FETCH_ASSOC) as $entry) {
      $entry['variables'] = unserialize($entry['variables']);
      $entries[] = $entry;
    }

    return $entries;
  }

}
