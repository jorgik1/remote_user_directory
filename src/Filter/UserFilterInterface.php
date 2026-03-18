<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Filter;

use Drupal\remote_user_directory\ValueObject\RemoteUser;
use Drupal\remote_user_directory\ValueObject\UserFilterContext;

/**
 * Determines whether a user should appear in the rendered block output.
 */
interface UserFilterInterface {

  /**
   * Indicates whether the given user should remain visible.
   *
   * @param \Drupal\remote_user_directory\ValueObject\RemoteUser $user
   *   The current remote user.
   * @param \Drupal\remote_user_directory\ValueObject\UserFilterContext $context
   *   The current listing context.
   *
   * @return bool
   *   TRUE to keep the user, FALSE to remove it.
   */
  public function shouldInclude(RemoteUser $user, UserFilterContext $context): bool;

}
