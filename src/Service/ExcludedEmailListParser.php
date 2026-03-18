<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Service;

/**
 * Parses and normalizes admin-managed email exclusions.
 */
final class ExcludedEmailListParser {

  /**
   * Parses newline-delimited or array-based email exclusions.
   *
   * @param string|array<int|string, mixed>|null $input
   *   The raw form or config input.
   *
   * @return array{emails: list<string>, invalidEntries: list<string>}
   *   A normalized result containing unique lowercase emails and invalid rows.
   */
  public function parse(string|array|null $input): array {
    $entries = is_array($input)
      ? $input
      : (preg_split('/\r\n|\r|\n/', (string) $input) ?: []);
    $emails = [];
    $invalidEntries = [];
    $seen = [];

    foreach ($entries as $entry) {
      $rawEntry = trim((string) $entry);
      if ($rawEntry === '') {
        continue;
      }

      $normalizedEntry = strtolower($rawEntry);
      if (filter_var($normalizedEntry, FILTER_VALIDATE_EMAIL) === FALSE) {
        $invalidEntries[] = $rawEntry;
        continue;
      }

      if (isset($seen[$normalizedEntry])) {
        continue;
      }

      $seen[$normalizedEntry] = TRUE;
      $emails[] = $normalizedEntry;
    }

    return [
      'emails' => $emails,
      'invalidEntries' => $invalidEntries,
    ];
  }

}
