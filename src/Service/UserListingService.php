<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\remote_user_directory\Client\ReqresUserApiClient;
use Drupal\remote_user_directory\Exception\RemoteUserDirectoryException;
use Drupal\remote_user_directory\Filter\UserFilterPipeline;
use Drupal\remote_user_directory\Normalizer\ReqresUserPageNormalizer;
use Drupal\remote_user_directory\ValueObject\RemoteUserPage;
use Psr\Log\LoggerInterface;

/**
 * Provides cached, normalized, and filtered remote user pages.
 */
final class UserListingService implements RemoteUserProviderInterface {

  private const CACHE_KEY_PREFIX = 'remote_user_directory:v2';

  public function __construct(
    private readonly ReqresUserApiClient $apiClient,
    private readonly ReqresUserPageNormalizer $normalizer,
    private readonly UserFilterPipeline $filterPipeline,
    private readonly CacheBackendInterface $cacheDefault,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getPage(int $page, int $perPage): RemoteUserPage {
    if ($page < 1) {
      throw new \InvalidArgumentException('Page numbers must be greater than or equal to 1.');
    }
    if ($perPage < 1) {
      throw new \InvalidArgumentException('Items per page must be greater than or equal to 1.');
    }

    $cacheId = sprintf('%s:%d:%d', self::CACHE_KEY_PREFIX, $page, $perPage);
    $cachedPage = $this->getCachedPage($cacheId);
    if ($cachedPage !== NULL && $this->isFresh($cacheId)) {
      return $this->filterPipeline->filter($cachedPage);
    }

    try {
      $payload = $this->apiClient->fetchPage($page, $perPage);
      $pageResult = $this->normalizer->normalize($payload);
      $this->cacheDefault->set($cacheId, [
        'page' => $pageResult,
        'fetched_at' => $this->time->getRequestTime(),
      ], Cache::PERMANENT);
      return $this->filterPipeline->filter($pageResult);
    }
    catch (RemoteUserDirectoryException $exception) {
      if ($cachedPage !== NULL) {
        $this->logger->warning('Falling back to stale remote user data: @message', [
          '@message' => $exception->getMessage(),
        ]);
        return $this->filterPipeline->filter($cachedPage);
      }

      throw $exception;
    }
  }

  /**
   * Determines whether a cached record is still fresh.
   *
   * @param string $cacheId
   *   The cache identifier.
   *
   * @return bool
   *   TRUE when the cached record is still fresh.
   */
  private function isFresh(string $cacheId): bool {
    /** @var object{data:mixed}|false $cacheItem */
    $cacheItem = $this->cacheDefault->get($cacheId);
    if ($cacheItem === FALSE || !is_array($cacheItem->data)) {
      return FALSE;
    }

    $fetchedAt = $cacheItem->data['fetched_at'] ?? NULL;
    if (!is_int($fetchedAt)) {
      return FALSE;
    }

    $cacheTtl = max(1, (int) $this->configFactory->get('remote_user_directory.settings')->get('cache_ttl'));
    return ($fetchedAt + $cacheTtl) >= $this->time->getRequestTime();
  }

  /**
   * Loads a cached page if one exists.
   *
   * @param string $cacheId
   *   The cache identifier.
   *
   * @return \Drupal\remote_user_directory\ValueObject\RemoteUserPage|null
   *   The cached page, or NULL when none exists.
   */
  private function getCachedPage(string $cacheId): ?RemoteUserPage {
    /** @var object{data:mixed}|false $cacheItem */
    $cacheItem = $this->cacheDefault->get($cacheId);
    if ($cacheItem === FALSE || !is_array($cacheItem->data)) {
      return NULL;
    }

    $page = $cacheItem->data['page'] ?? NULL;
    return $page instanceof RemoteUserPage ? $page : NULL;
  }

}
