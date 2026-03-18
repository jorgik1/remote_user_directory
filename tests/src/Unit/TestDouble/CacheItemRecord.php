<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\TestDouble;

use Drupal\Core\Cache\CacheBackendInterface;

final class CacheItemRecord {

  /**
   * @param array<string> $tags
   */
  public function __construct(
    public mixed $data,
    public int $created = 0,
    public array $tags = [],
    public bool $valid = TRUE,
    public int $expire = CacheBackendInterface::CACHE_PERMANENT,
    public string $checksum = '',
    public int $serialized = 0,
  ) {}

}
