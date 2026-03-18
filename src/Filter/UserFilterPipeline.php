<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Filter;

use Drupal\remote_user_directory\ValueObject\RemoteUserPage;
use Drupal\remote_user_directory\ValueObject\UserFilterContext;

/**
 * Applies all tagged user filters to a remote user page.
 */
final readonly class UserFilterPipeline {

  /**
   * Creates the filter pipeline.
   *
   * @param iterable<\Drupal\remote_user_directory\Filter\UserFilterInterface> $filters
   *   The tagged user filters in execution order.
   */
  public function __construct(
    private iterable $filters,
  ) {}

  /**
   * Filters the current page without changing remote pagination metadata.
   *
   * @param \Drupal\remote_user_directory\ValueObject\RemoteUserPage $page
   *   The normalized remote page.
   *
   * @return \Drupal\remote_user_directory\ValueObject\RemoteUserPage
   *   The filtered page.
   */
  public function filter(RemoteUserPage $page): RemoteUserPage {
    $context = new UserFilterContext(
      page: $page->page,
      perPage: $page->perPage,
      remoteTotal: $page->total,
      remoteTotalPages: $page->totalPages,
    );

    $filtered = [];
    foreach ($page->items as $user) {
      $include = TRUE;
      foreach ($this->filters as $filter) {
        if (!$filter->shouldInclude($user, $context)) {
          $include = FALSE;
          break;
        }
      }

      if ($include) {
        $filtered[] = $user;
      }
    }

    return $page->withItems($filtered);
  }

}
