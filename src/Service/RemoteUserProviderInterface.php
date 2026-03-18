<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Service;

use Drupal\remote_user_directory\ValueObject\RemoteUserPage;

/**
 * Provides remote user pages to Drupal adapters.
 */
interface RemoteUserProviderInterface {

  /**
   * Returns a remote user page.
   *
   * @param int $page
   *   The one-based page number.
   * @param int $perPage
   *   The number of users to request.
   *
   * @return \Drupal\remote_user_directory\ValueObject\RemoteUserPage
   *   The remote user page.
   */
  public function getPage(int $page, int $perPage): RemoteUserPage;

}
