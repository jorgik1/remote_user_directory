<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Client;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\remote_user_directory\Exception\InvalidResponseException;
use Drupal\remote_user_directory\Exception\MissingApiKeyException;
use Drupal\remote_user_directory\Exception\RemoteApiException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Performs raw HTTP requests against the ReqRes users endpoint.
 */
final readonly class ReqresUserApiClient {

  public function __construct(
    private ClientInterface $httpClient,
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Fetches a single users page from ReqRes.
   *
   * @return array<string, mixed>
   *   The decoded JSON payload.
   */
  public function fetchPage(int $page, int $perPage): array {
    $config = $this->configFactory->get('remote_user_directory.settings');
    $apiKey = trim((string) $config->get('api_key'));
    if ($apiKey === '') {
      throw new MissingApiKeyException('A ReqRes API key has not been configured.');
    }

    $baseUri = rtrim((string) $config->get('base_uri'), '/');
    $timeout = max(1, (int) $config->get('timeout'));

    try {
      $response = $this->httpClient->request(Request::METHOD_GET, $baseUri . '/users', [
        'headers' => [
          'Accept' => 'application/json',
          'x-api-key' => $apiKey,
        ],
        'http_errors' => FALSE,
        'query' => [
          'page' => $page,
          'per_page' => $perPage,
        ],
        'timeout' => $timeout,
      ]);
    }
    catch (GuzzleException $exception) {
      throw new RemoteApiException('The remote API request failed.', previous: $exception);
    }

    $statusCode = $response->getStatusCode();
    if ($statusCode < 200 || $statusCode >= 300) {
      throw new RemoteApiException(sprintf('The remote API returned HTTP %d.', $statusCode));
    }

    try {
      $data = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $exception) {
      throw new InvalidResponseException('The remote API returned invalid JSON.', previous: $exception);
    }

    if (!is_array($data)) {
      throw new InvalidResponseException('The remote API response must decode to an array.');
    }

    return $data;
  }

}
