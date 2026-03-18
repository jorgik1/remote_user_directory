<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\Client;

use Drupal\remote_user_directory\Client\ReqresUserApiClient;
use Drupal\remote_user_directory\Exception\InvalidResponseException;
use Drupal\remote_user_directory\Exception\MissingApiKeyException;
use Drupal\remote_user_directory\Exception\RemoteApiException;
use Drupal\Tests\remote_user_directory\Unit\TestDouble\CreatesConfigFactoryTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ReqresUserApiClientTest extends TestCase {

  use CreatesConfigFactoryTrait;

  public function testFetchPageBuildsRequestAndReturnsData(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        'https://reqres.in/api/users',
        $this->callback(function (array $options): bool {
          return $options['headers']['x-api-key'] === 'secret'
            && $options['query']['page'] === 2
            && $options['query']['per_page'] === 15
            && $options['timeout'] === 5
            && $options['http_errors'] === FALSE;
        }),
      )
      ->willReturn(new Response(200, [], json_encode([
        'page' => 2,
        'per_page' => 15,
        'total' => 20,
        'total_pages' => 2,
        'data' => [],
      ], JSON_THROW_ON_ERROR)));

    $client = new ReqresUserApiClient($httpClient, $this->createConfigFactory([
      'api_key' => 'secret',
      'base_uri' => 'https://reqres.in/api',
      'timeout' => 5,
    ]));

    $payload = $client->fetchPage(2, 15);

    self::assertSame(2, $payload['page']);
    self::assertSame(15, $payload['per_page']);
  }

  public function testFetchPageThrowsWhenApiKeyIsMissing(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->never())->method('request');

    $client = new ReqresUserApiClient($httpClient, $this->createConfigFactory([
      'api_key' => '',
      'base_uri' => 'https://reqres.in/api',
      'timeout' => 3,
    ]));

    $this->expectException(MissingApiKeyException::class);
    $client->fetchPage(1, 10);
  }

  public function testFetchPageTranslatesTransportErrors(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->method('request')
      ->willThrowException(new RequestException('boom', new Request('GET', 'https://reqres.in/api/users')));

    $client = new ReqresUserApiClient($httpClient, $this->createConfigFactory([
      'api_key' => 'secret',
      'base_uri' => 'https://reqres.in/api',
      'timeout' => 3,
    ]));

    $this->expectException(RemoteApiException::class);
    $client->fetchPage(1, 10);
  }

  public function testFetchPageRejectsMalformedJson(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->method('request')
      ->willReturn(new Response(200, [], '{'));

    $client = new ReqresUserApiClient($httpClient, $this->createConfigFactory([
      'api_key' => 'secret',
      'base_uri' => 'https://reqres.in/api',
      'timeout' => 3,
    ]));

    $this->expectException(InvalidResponseException::class);
    $client->fetchPage(1, 10);
  }
}
