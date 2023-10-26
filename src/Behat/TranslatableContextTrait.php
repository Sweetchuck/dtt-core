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

    // On non GNU based systems (e.g.: Alpine) \GLOB_BRACE is not available.
    return array_unique(array_merge(
      glob("$translationDir/*.xliff") ?: [],
      glob("$translationDir/*.php") ?: [],
      glob("$translationDir/*.yml") ?: [],
    ));
  }

  protected static function getTranslationDir(): string {
    $namespaceParts = explode('\\', static::class);
    $prefix = implode('\\', array_slice($namespaceParts, 0, static::$i18nNamespaceDepth));

    if (!isset(static::$i18nDirs[$prefix])) {
      $composerFileName = 'composer.json';
      $reflection = new \ReflectionClass(static::class);

      static::$i18nDirs[$prefix] = Path::join(
        static::findFileUpward($composerFileName, Path::getDirectory($reflection->getFileName())),
        'i18n',
      );
    }

    return Path::join(static::$i18nDirs[$prefix], (string) end($namespaceParts));
  }

  /**
   * @param string $fileName
   * @param string $currentDir
   * @param null|string $rootDir
   *   Do not go above this directory.
   *
   * @return null|string
   *   Returns NULL if the $fileName not exists in any of the parent directories,
   *   returns the parent directory without the $fileName if the $fileName
   *   exists in one of the parent directory.
   *
   * @link https://github.com/Sweetchuck/utils/blob/1.x/src/Filesystem.php
   */
  protected static function findFileUpward(
    string $fileName,
    string $currentDir,
    ?string $rootDir = null
  ): ?string {
    if ($rootDir !== null && !static::isParentDirOrSame($rootDir, $currentDir)) {
      throw new \InvalidArgumentException("The '$rootDir' is not parent dir of '$currentDir'");
    }

    while ($currentDir && ($rootDir === null || static::isParentDirOrSame($rootDir, $currentDir))) {
      if (file_exists("$currentDir/$fileName")) {
        return $currentDir;
      }

      $parentDir = Path::getDirectory($currentDir);
      if ($currentDir === $parentDir) {
        break;
      }

      $currentDir = $parentDir;
    }

    return null;
  }

  /**
   * @link https://github.com/Sweetchuck/utils/blob/1.x/src/Filesystem.php
   */
  protected static function isParentDirOrSame(string $parentDir, string $childDir): bool
  {
    # @todo Handle a/./b and a/../c formats.
    if ($parentDir === '.') {
      $parentDir = './';
    }

    if ($childDir === '.') {
      $childDir = './';
    }

    $parentDir = preg_replace('@^\./@', '', $parentDir);
    $childDir = preg_replace('@^\./@', '', $childDir);
    $pattern = '@^' . preg_quote($parentDir, '@') . '(/|$)@';

    return (bool) preg_match($pattern, $childDir);
  }

}
