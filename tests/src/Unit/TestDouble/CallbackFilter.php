<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\TestDouble;

use Drupal\remote_user_directory\Filter\UserFilterInterface;
use Drupal\remote_user_directory\ValueObject\RemoteUser;
use Drupal\remote_user_directory\ValueObject\UserFilterContext;

final class CallbackFilter implements UserFilterInterface {

  /**
   * @param \Closure(\Drupal\remote_user_directory\ValueObject\RemoteUser, \Drupal\remote_user_directory\ValueObject\UserFilterContext): bool $callback
   */
  public function __construct(
    private readonly \Closure $callback,
  ) {}

  public function shouldInclude(RemoteUser $user, UserFilterContext $context): bool {
    return ($this->callback)($user, $context);
  }

}
