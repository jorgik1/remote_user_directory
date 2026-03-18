<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\ValueObject;

/**
 * Immutable view of a single remote user.
 */
final readonly class RemoteUser {

  public function __construct(
    public string $email,
    public string $forename,
    public string $surname,
  ) {}

}
