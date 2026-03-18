<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Normalizer;

use Drupal\remote_user_directory\Exception\InvalidResponseException;
use Drupal\remote_user_directory\ValueObject\RemoteUser;
use Drupal\remote_user_directory\ValueObject\RemoteUserPage;

/**
 * Normalizes ReqRes responses into typed value objects.
 */
final class ReqresUserPageNormalizer {

  /**
   * Converts a ReqRes payload into a remote user page.
   *
   * @param array<string, mixed> $payload
   *   The decoded JSON payload.
   *
   * @return \Drupal\remote_user_directory\ValueObject\RemoteUserPage
   *   The normalized page.
   */
  public function normalize(array $payload): RemoteUserPage {
    $items = [];
    $rawItems = $payload['data'] ?? NULL;
    if (!is_array($rawItems)) {
      throw new InvalidResponseException('The response is missing the data array.');
    }

    foreach ($rawItems as $rawItem) {
      if (!is_array($rawItem)) {
        throw new InvalidResponseException('A user entry must be an array.');
      }

      $items[] = new RemoteUser(
        email: $this->readString($rawItem, 'email'),
        forename: $this->readString($rawItem, 'first_name'),
        surname: $this->readString($rawItem, 'last_name'),
      );
    }

    return new RemoteUserPage(
      items: $items,
      page: $this->readInt($payload, 'page'),
      perPage: $this->readInt($payload, 'per_page'),
      total: $this->readInt($payload, 'total'),
      totalPages: $this->readInt($payload, 'total_pages'),
    );
  }

  /**
   * Reads an integer value from the payload.
   *
   * @param array<string, mixed> $payload
   *   The decoded payload.
   * @param string $key
   *   The expected key.
   */
  private function readInt(array $payload, string $key): int {
    $value = $payload[$key] ?? NULL;
    if (!is_int($value)) {
      throw new InvalidResponseException(sprintf('The "%s" value must be an integer.', $key));
    }

    return $value;
  }

  /**
   * Reads a non-empty string value from the payload.
   *
   * @param array<string, mixed> $payload
   *   The decoded payload.
   * @param string $key
   *   The expected key.
   */
  private function readString(array $payload, string $key): string {
    $value = $payload[$key] ?? NULL;
    if (!is_string($value) || trim($value) === '') {
      throw new InvalidResponseException(sprintf('The "%s" value must be a non-empty string.', $key));
    }

    return $value;
  }

}
