<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\Service;

use Drupal\remote_user_directory\Service\ExcludedEmailListParser;
use PHPUnit\Framework\TestCase;

final class ExcludedEmailListParserTest extends TestCase {

  public function testParseNormalizesDeduplicatesAndReportsInvalidEntries(): void {
    $parser = new ExcludedEmailListParser();

    $result = $parser->parse(implode(PHP_EOL, [
      'Blocked@Example.com',
      ' blocked@example.com ',
      '',
      'invalid',
      'Allowed@example.com',
    ]));

    self::assertSame([
      'blocked@example.com',
      'allowed@example.com',
    ], $result['emails']);
    self::assertSame(['invalid'], $result['invalidEntries']);
  }

  public function testParseAcceptsArrayInput(): void {
    $parser = new ExcludedEmailListParser();

    $result = $parser->parse([
      'FIRST@example.com',
      'second@example.com',
    ]);

    self::assertSame([
      'first@example.com',
      'second@example.com',
    ], $result['emails']);
    self::assertSame([], $result['invalidEntries']);
  }

}
