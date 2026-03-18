<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\TestDouble;

use Drupal\Component\Datetime\TimeInterface;

final readonly class FrozenTime implements TimeInterface {

  public function __construct(
    private int $currentTime,
  ) {}

  public function getRequestTime(): int {
    return $this->currentTime;
  }

  public function getRequestMicroTime(): float {
    return (float) $this->currentTime;
  }

  public function getCurrentTime(): int {
    return $this->currentTime;
  }

  public function getCurrentMicroTime(): float {
    return (float) $this->currentTime;
  }

}
