<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\TestDouble;

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;

final class IdentityTranslation implements TranslationInterface {

  /**
   * @param array<string, mixed> $args
   * @param array<string, mixed> $options
   */
  public function translate($string, array $args = [], array $options = []): TranslatableMarkup {
    return new TranslatableMarkup($string, $args, $options, $this);
  }

  public function translateString(TranslatableMarkup $translated_string): string {
    return $translated_string->getUntranslatedString();
  }

  /**
   * @param array<string, mixed> $args
   * @param array<string, mixed> $options
   */
  public function formatPlural($count, $singular, $plural, array $args = [], array $options = []): PluralTranslatableMarkup {
    return new PluralTranslatableMarkup((int) $count, $singular, $plural, $args, $options, $this);
  }

}
