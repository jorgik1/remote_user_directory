<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\ValueObject;

/**
 * Context passed to user filters during a listing request.
 */
final readonly class UserFilterContext {

  public function __construct(
    public int $page,
    public int $perPage,
    public int $remoteTotal,
    public int $remoteTotalPages,
  ) {}

}
