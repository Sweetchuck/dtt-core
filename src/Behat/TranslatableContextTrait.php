<?php

declare(strict_types = 1);

namespace Sweetchuck\DrupalTestTraits\Core\Behat;

use Sweetchuck\Utils\Filesystem as FilesystemUtils;
use Symfony\Component\Filesystem\Path;

/**
 * @see \Behat\Behat\Context\TranslatableContext
 */
trait TranslatableContextTrait {

  /**
   * Directory mapping.
   *
   * Key: namespace.
   * Value: Filesystem directory.
   *
   * @var string[]
   */
  protected static array $i18nDirs = [];

  protected static int $i18nNamespaceDepth = 3;

  /**
   * {@inheritdoc}
   */
  public static function getTranslationResources() {
    $translationDir = static::getTranslationDir();

    return glob("$translationDir/*.{xliff,php,yml}", GLOB_BRACE);
  }

  protected static function getTranslationDir(): string {
    $namespaceParts = explode('\\', static::class);
    $prefix = implode('\\', array_slice($namespaceParts, 0, static::$i18nNamespaceDepth));

    if (!isset(static::$i18nDirs[$prefix])) {
      $composerFileName = 'composer.json';
      $reflection = new \ReflectionClass(static::class);

      static::$i18nDirs[$prefix] = Path::join(
        FilesystemUtils::findFileUpward($composerFileName, Path::getDirectory($reflection->getFileName())),
        'i18n',
      );
    }

    return Path::join(static::$i18nDirs[$prefix], (string) end($namespaceParts));
  }

}
