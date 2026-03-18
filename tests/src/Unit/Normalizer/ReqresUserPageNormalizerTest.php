<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\Normalizer;

use Drupal\remote_user_directory\Exception\InvalidResponseException;
use Drupal\remote_user_directory\Normalizer\ReqresUserPageNormalizer;
use PHPUnit\Framework\TestCase;

final class ReqresUserPageNormalizerTest extends TestCase {

  public function testNormalizeMapsTheReqresPayload(): void {
    $normalizer = new ReqresUserPageNormalizer();

    $page = $normalizer->normalize([
      'page' => 1,
      'per_page' => 10,
      'total' => 12,
      'total_pages' => 2,
      'data' => [
        [
          'email' => 'jane@example.com',
          'first_name' => 'Jane',
          'last_name' => 'Doe',
        ],
      ],
    ]);

    self::assertCount(1, $page->items);
    self::assertSame('jane@example.com', $page->items[0]->email);
    self::assertSame('Jane', $page->items[0]->forename);
    self::assertSame('Doe', $page->items[0]->surname);
    self::assertSame(12, $page->total);
    self::assertSame(2, $page->totalPages);
  }

  public function testNormalizeRejectsInvalidPayloads(): void {
    $normalizer = new ReqresUserPageNormalizer();

    $this->expectException(InvalidResponseException::class);
    $normalizer->normalize([
      'page' => 1,
      'per_page' => 10,
      'total' => 12,
      'total_pages' => 2,
      'data' => [
        ['email' => 'jane@example.com'],
      ],
    ]);
  }
}
