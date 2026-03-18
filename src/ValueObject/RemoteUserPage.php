<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\ValueObject;

/**
 * Immutable representation of a filtered remote user page.
 *
 * @phpstan-type RemoteUserList list<\Drupal\remote_user_directory\ValueObject\RemoteUser>
 */
final readonly class RemoteUserPage {

  /**
   * Creates a remote user page value object.
   *
   * @param list<\Drupal\remote_user_directory\ValueObject\RemoteUser> $items
   *   The users returned for the current page.
   * @param int $page
   *   The current one-based page number.
   * @param int $perPage
   *   The number of users requested per page.
   * @param int $total
   *   The total number of remote users before filtering.
   * @param int $totalPages
   *   The total number of remote pages before filtering.
   */
  public function __construct(
    public array $items,
    public int $page,
    public int $perPage,
    public int $total,
    public int $totalPages,
  ) {}

  /**
   * Returns a copy of the page with a different item list.
   *
   * @param list<\Drupal\remote_user_directory\ValueObject\RemoteUser> $items
   *   The filtered user list.
   *
   * @return self
   *   The filtered page copy.
   */
  public function withItems(array $items): self {
    return new self(
      items: $items,
      page: $this->page,
      perPage: $this->perPage,
      total: $this->total,
      totalPages: $this->totalPages,
    );
  }

}
