<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Filter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\remote_user_directory\Service\ExcludedEmailListParser;
use Drupal\remote_user_directory\ValueObject\RemoteUser;
use Drupal\remote_user_directory\ValueObject\UserFilterContext;

/**
 * Removes users whose email addresses are excluded in module configuration.
 */
final class ConfiguredEmailExclusionFilter implements UserFilterInterface {

  /**
   * Cached excluded email addresses for the current request.
   *
   * @var array<string, true>|null
   */
  private ?array $excludedEmails = NULL;

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ExcludedEmailListParser $excludedEmailListParser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function shouldInclude(RemoteUser $user, UserFilterContext $context): bool {
    return !isset($this->getExcludedEmails()[strtolower($user->email)]);
  }

  /**
   * Loads the configured exclusion list once per request.
   *
   * @return array<string, true>
   *   The configured excluded email addresses keyed by normalized email.
   */
  private function getExcludedEmails(): array {
    if ($this->excludedEmails !== NULL) {
      return $this->excludedEmails;
    }

    $parsedEmails = $this->excludedEmailListParser->parse(
      $this->configFactory->get('remote_user_directory.settings')->get('excluded_emails'),
    );
    $this->excludedEmails = array_fill_keys($parsedEmails['emails'], TRUE);
    return $this->excludedEmails;
  }

}
