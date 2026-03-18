<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\Filter;

use Drupal\remote_user_directory\Filter\ConfiguredEmailExclusionFilter;
use Drupal\remote_user_directory\Filter\UserFilterPipeline;
use Drupal\remote_user_directory\Service\ExcludedEmailListParser;
use Drupal\remote_user_directory\ValueObject\RemoteUser;
use Drupal\remote_user_directory\ValueObject\RemoteUserPage;
use Drupal\Tests\remote_user_directory\Unit\TestDouble\CallbackFilter;
use Drupal\Tests\remote_user_directory\Unit\TestDouble\CreatesConfigFactoryTrait;
use PHPUnit\Framework\TestCase;

final class UserFilterPipelineTest extends TestCase {

  use CreatesConfigFactoryTrait;

  public function testFiltersRunInOrderAndCanExcludeAUser(): void {
    $order = [];
    $pipeline = new UserFilterPipeline([
      new CallbackFilter(function (RemoteUser $user) use (&$order): bool {
        $order[] = 'first:' . $user->email;
        return TRUE;
      }),
      new CallbackFilter(function (RemoteUser $user) use (&$order): bool {
        $order[] = 'second:' . $user->email;
        return $user->email !== 'blocked@example.com';
      }),
    ]);

    $result = $pipeline->filter(new RemoteUserPage(
      items: [
        new RemoteUser('allowed@example.com', 'Allowed', 'User'),
        new RemoteUser('blocked@example.com', 'Blocked', 'User'),
      ],
      page: 1,
      perPage: 10,
      total: 2,
      totalPages: 1,
    ));

    self::assertSame([
      'first:allowed@example.com',
      'second:allowed@example.com',
      'first:blocked@example.com',
      'second:blocked@example.com',
    ], $order);
    self::assertCount(1, $result->items);
    self::assertSame('allowed@example.com', $result->items[0]->email);
  }

  public function testConfiguredAndCustomFiltersCanStack(): void {
    $customFilterOrder = [];
    $pipeline = new UserFilterPipeline([
      new ConfiguredEmailExclusionFilter(
        $this->createConfigFactory([
          'excluded_emails' => [
            'blocked@example.com',
          ],
        ]),
        new ExcludedEmailListParser(),
      ),
      new CallbackFilter(function (RemoteUser $user) use (&$customFilterOrder): bool {
        $customFilterOrder[] = $user->email;
        return $user->email !== 'custom@example.com';
      }),
    ]);

    $result = $pipeline->filter(new RemoteUserPage(
      items: [
        new RemoteUser('allowed@example.com', 'Allowed', 'User'),
        new RemoteUser('blocked@example.com', 'Blocked', 'User'),
        new RemoteUser('custom@example.com', 'Custom', 'User'),
      ],
      page: 1,
      perPage: 10,
      total: 3,
      totalPages: 1,
    ));

    self::assertSame([
      'allowed@example.com',
      'custom@example.com',
    ], $customFilterOrder);
    self::assertSame(['allowed@example.com'], array_map(
      static fn (RemoteUser $user): string => $user->email,
      $result->items,
    ));
  }

}
