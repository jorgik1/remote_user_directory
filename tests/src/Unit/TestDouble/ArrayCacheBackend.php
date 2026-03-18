<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\TestDouble;

use Drupal\Core\Cache\CacheBackendInterface;

final class ArrayCacheBackend implements CacheBackendInterface {

  /**
   * @var array<string, \Drupal\Tests\remote_user_directory\Unit\TestDouble\CacheItemRecord>
   */
  private array $items = [];

  /**
   * @param string $cid
   * @param bool $allow_invalid
   *
   * @return \Drupal\Tests\remote_user_directory\Unit\TestDouble\CacheItemRecord|false
   */
  public function get($cid, $allow_invalid = FALSE) {
    $item = $this->items[$cid] ?? FALSE;
    if ($item === FALSE) {
      return FALSE;
    }
    if (!$allow_invalid && isset($item->valid) && $item->valid === FALSE) {
      return FALSE;
    }

    return $item;
  }

  /**
   * @param array<int, string> $cids
   * @param bool $allow_invalid
   *
   * @return array<string, \Drupal\Tests\remote_user_directory\Unit\TestDouble\CacheItemRecord>
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE): array {
    $items = [];
    foreach ($cids as $key => $cid) {
      $item = $this->get($cid, $allow_invalid);
      if ($item !== FALSE) {
        $items[$cid] = $item;
        unset($cids[$key]);
      }
    }
    return $items;
  }

  /**
   * @param string $cid
   * @param array<string> $tags
   */
  public function set($cid, $data, $expire = self::CACHE_PERMANENT, array $tags = []): void {
    $this->items[$cid] = new CacheItemRecord(
      data: $data,
      created: 0,
      tags: $tags,
      valid: TRUE,
      expire: $expire,
    );
  }

  /**
   * @param array<string, array{data: mixed, expire?: int, tags?: array<string>}> $items
   */
  public function setMultiple(array $items): void {
    foreach ($items as $cid => $item) {
      $this->set($cid, $item['data'], $item['expire'] ?? self::CACHE_PERMANENT, $item['tags'] ?? []);
    }
  }

  /**
   * @param string $cid
   */
  public function delete($cid): void {
    unset($this->items[$cid]);
  }

  /**
   * @param array<int, string> $cids
   */
  public function deleteMultiple(array $cids): void {
    foreach ($cids as $cid) {
      $this->delete($cid);
    }
  }

  public function deleteAll(): void {
    $this->items = [];
  }

  /**
   * @param string $cid
   */
  public function invalidate($cid): void {
    if (isset($this->items[$cid])) {
      $this->items[$cid]->valid = FALSE;
    }
  }

  /**
   * @param array<int, string> $cids
   */
  public function invalidateMultiple(array $cids): void {
    foreach ($cids as $cid) {
      $this->invalidate($cid);
    }
  }

  public function invalidateAll(): void {
    foreach (array_keys($this->items) as $cid) {
      $this->invalidate($cid);
    }
  }

  public function garbageCollection(): void {
  }

  public function removeBin(): void {
    $this->deleteAll();
  }

}
