<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\Filter;

use Drupal\remote_user_directory\Filter\ConfiguredEmailExclusionFilter;
use Drupal\remote_user_directory\Service\ExcludedEmailListParser;
use Drupal\remote_user_directory\ValueObject\RemoteUser;
use Drupal\remote_user_directory\ValueObject\UserFilterContext;
use Drupal\Tests\remote_user_directory\Unit\TestDouble\CreatesConfigFactoryTrait;
use PHPUnit\Framework\TestCase;

final class ConfiguredEmailExclusionFilterTest extends TestCase {

  use CreatesConfigFactoryTrait;

  public function testConfiguredEmailsExcludeUsersCaseInsensitively(): void {
    $filter = new ConfiguredEmailExclusionFilter(
      $this->createConfigFactory([
        'excluded_emails' => [
          '',
          'Blocked@Example.com',
          'blocked@example.com',
        ],
      ]),
      new ExcludedEmailListParser(),
    );

    $context = new UserFilterContext(1, 10, 2, 1);

    self::assertFalse($filter->shouldInclude(
      new RemoteUser('blocked@example.com', 'Blocked', 'User'),
      $context,
    ));
    self::assertTrue($filter->shouldInclude(
      new RemoteUser('allowed@example.com', 'Allowed', 'User'),
      $context,
    ));
  }

}
