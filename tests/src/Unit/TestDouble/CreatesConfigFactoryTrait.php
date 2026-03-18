<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\TestDouble;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

trait CreatesConfigFactoryTrait {

  /**
   * @param array<string, mixed> $values
   */
  private function createConfigFactory(array $values): ConfigFactoryInterface {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(static fn (string $key): mixed => $values[$key] ?? NULL);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('remote_user_directory.settings')
      ->willReturn($config);

    return $configFactory;
  }

}
