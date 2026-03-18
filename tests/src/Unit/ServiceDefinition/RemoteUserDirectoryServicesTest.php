<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\ServiceDefinition;

use Drupal\remote_user_directory\Filter\ConfiguredEmailExclusionFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class RemoteUserDirectoryServicesTest extends TestCase {

  public function testConfiguredEmailExclusionFilterIsTaggedWithPriority(): void {
    $services = Yaml::parseFile(
      dirname(__DIR__, 4) . '/remote_user_directory.services.yml',
      Yaml::PARSE_CUSTOM_TAGS,
    );

    self::assertSame([
      [
        'name' => 'remote_user_directory.user_filter',
        'priority' => 0,
      ],
    ], $services['services'][ConfiguredEmailExclusionFilter::class]['tags']);
  }

}
