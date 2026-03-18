<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\Service;

use Drupal\remote_user_directory\Client\ReqresUserApiClient;
use Drupal\remote_user_directory\Exception\MissingApiKeyException;
use Drupal\remote_user_directory\Filter\ConfiguredEmailExclusionFilter;
use Drupal\remote_user_directory\Filter\UserFilterPipeline;
use Drupal\remote_user_directory\Normalizer\ReqresUserPageNormalizer;
use Drupal\remote_user_directory\Service\ExcludedEmailListParser;
use Drupal\remote_user_directory\Service\UserListingService;
use Drupal\remote_user_directory\ValueObject\RemoteUser;
use Drupal\remote_user_directory\ValueObject\RemoteUserPage;
use Drupal\Tests\remote_user_directory\Unit\TestDouble\ArrayCacheBackend;
use Drupal\Tests\remote_user_directory\Unit\TestDouble\CreatesConfigFactoryTrait;
use Drupal\Tests\remote_user_directory\Unit\TestDouble\FrozenTime;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UserListingServiceTest extends TestCase {

  use CreatesConfigFactoryTrait;

  public function testGetPageReturnsFreshCachedDataWithoutCallingRemote(): void {
    $cache = new ArrayCacheBackend();
    $cachedPage = new RemoteUserPage(
      items: [new RemoteUser('cached@example.com', 'Cached', 'User')],
      page: 1,
      perPage: 10,
      total: 1,
      totalPages: 1,
    );
    $cache->set('remote_user_directory:v2:1:10', [
      'page' => $cachedPage,
      'fetched_at' => 1000,
    ]);

    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->never())->method('request');

    $configFactory = $this->createConfigFactory([
      'api_key' => 'secret',
      'base_uri' => 'https://reqres.in/api',
      'timeout' => 3,
      'cache_ttl' => 300,
    ]);

    $service = new UserListingService(
      new ReqresUserApiClient($httpClient, $configFactory),
      new ReqresUserPageNormalizer(),
      new UserFilterPipeline([]),
      $cache,
      $configFactory,
      new FrozenTime(1000),
      $this->createMock(LoggerInterface::class),
    );

    $result = $service->getPage(1, 10);

    self::assertEquals($cachedPage, $result);
  }

  public function testGetPageCachesRemoteData(): void {
    $cache = new ArrayCacheBackend();
    $normalized = new RemoteUserPage(
      items: [new RemoteUser('remote@example.com', 'Remote', 'User')],
      page: 1,
      perPage: 10,
      total: 1,
      totalPages: 1,
    );

    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->once())
      ->method('request')
      ->willReturn(new Response(200, [], json_encode([
        'page' => 1,
        'per_page' => 10,
        'total' => 1,
        'total_pages' => 1,
        'data' => [
          [
            'email' => 'remote@example.com',
            'first_name' => 'Remote',
            'last_name' => 'User',
          ],
        ],
      ], JSON_THROW_ON_ERROR)));

    $configFactory = $this->createConfigFactory([
      'api_key' => 'secret',
      'base_uri' => 'https://reqres.in/api',
      'timeout' => 3,
      'cache_ttl' => 300,
    ]);

    $service = new UserListingService(
      new ReqresUserApiClient($httpClient, $configFactory),
      new ReqresUserPageNormalizer(),
      new UserFilterPipeline([]),
      $cache,
      $configFactory,
      new FrozenTime(1000),
      $this->createMock(LoggerInterface::class),
    );

    $result = $service->getPage(1, 10);

    self::assertEquals($normalized, $result);
    $cacheItem = $cache->get('remote_user_directory:v2:1:10');
    self::assertNotFalse($cacheItem);
    self::assertEquals($normalized, $cacheItem->data['page']);
  }

  public function testGetPageAppliesUpdatedExclusionsToFreshCachedData(): void {
    $cache = new ArrayCacheBackend();
    $cachedPage = new RemoteUserPage(
      items: [new RemoteUser('cached@example.com', 'Cached', 'User')],
      page: 1,
      perPage: 10,
      total: 1,
      totalPages: 1,
    );
    $cache->set('remote_user_directory:v2:1:10', [
      'page' => $cachedPage,
      'fetched_at' => 1000,
    ]);

    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->never())->method('request');

    $configFactory = $this->createConfigFactory([
      'api_key' => 'secret',
      'base_uri' => 'https://reqres.in/api',
      'timeout' => 3,
      'cache_ttl' => 300,
      'excluded_emails' => ['cached@example.com'],
    ]);

    $service = new UserListingService(
      new ReqresUserApiClient($httpClient, $configFactory),
      new ReqresUserPageNormalizer(),
      new UserFilterPipeline([
        new ConfiguredEmailExclusionFilter($configFactory, new ExcludedEmailListParser()),
      ]),
      $cache,
      $configFactory,
      new FrozenTime(1000),
      $this->createMock(LoggerInterface::class),
    );

    $result = $service->getPage(1, 10);

    self::assertSame([], $result->items);
    $cacheItem = $cache->get('remote_user_directory:v2:1:10');
    self::assertNotFalse($cacheItem);
    self::assertCount(1, $cacheItem->data['page']->items);
  }

  public function testGetPageFallsBackToFilteredStaleCache(): void {
    $cache = new ArrayCacheBackend();
    $stalePage = new RemoteUserPage(
      items: [new RemoteUser('stale@example.com', 'Stale', 'User')],
      page: 1,
      perPage: 10,
      total: 1,
      totalPages: 1,
    );
    $cache->set('remote_user_directory:v2:1:10', [
      'page' => $stalePage,
      'fetched_at' => 100,
    ]);

    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->never())->method('request');

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('warning');

    $configFactory = $this->createConfigFactory([
      'api_key' => '',
      'base_uri' => 'https://reqres.in/api',
      'timeout' => 3,
      'cache_ttl' => 300,
      'excluded_emails' => ['stale@example.com'],
    ]);

    $service = new UserListingService(
      new ReqresUserApiClient($httpClient, $configFactory),
      new ReqresUserPageNormalizer(),
      new UserFilterPipeline([
        new ConfiguredEmailExclusionFilter($configFactory, new ExcludedEmailListParser()),
      ]),
      $cache,
      $configFactory,
      new FrozenTime(1000),
      $logger,
    );

    $result = $service->getPage(1, 10);

    self::assertSame([], $result->items);
  }

  public function testGetPageThrowsWhenNoCacheExists(): void {
    $cache = new ArrayCacheBackend();
    $httpClient = $this->createMock(ClientInterface::class);

    $configFactory = $this->createConfigFactory([
      'api_key' => '',
      'base_uri' => 'https://reqres.in/api',
      'timeout' => 3,
      'cache_ttl' => 300,
    ]);

    $service = new UserListingService(
      new ReqresUserApiClient($httpClient, $configFactory),
      new ReqresUserPageNormalizer(),
      new UserFilterPipeline([]),
      $cache,
      $configFactory,
      new FrozenTime(1000),
      $this->createMock(LoggerInterface::class),
    );

    $this->expectException(MissingApiKeyException::class);
    $service->getPage(1, 10);
  }

}
