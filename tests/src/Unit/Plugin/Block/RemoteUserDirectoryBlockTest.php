<?php

declare(strict_types=1);

namespace Drupal\Tests\remote_user_directory\Unit\Plugin\Block;

use Drupal\Core\Form\FormState;
use Drupal\Core\Pager\Pager;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\remote_user_directory\Exception\RemoteUserDirectoryException;
use Drupal\remote_user_directory\Plugin\Block\RemoteUserDirectoryBlock;
use Drupal\remote_user_directory\Service\RemoteUserProviderInterface;
use Drupal\remote_user_directory\ValueObject\RemoteUser;
use Drupal\remote_user_directory\ValueObject\RemoteUserPage;
use Drupal\Tests\remote_user_directory\Unit\TestDouble\CreatesConfigFactoryTrait;
use Drupal\Tests\remote_user_directory\Unit\TestDouble\IdentityTranslation;
use PHPUnit\Framework\TestCase;

final class RemoteUserDirectoryBlockTest extends TestCase {

  use CreatesConfigFactoryTrait;

  public function testDefaultConfigurationProvidesExpectedValues(): void {
    $block = $this->createBlock();

    self::assertSame([
      'items_per_page' => 10,
      'email_label' => 'Email',
      'forename_label' => 'Forename',
      'surname_label' => 'Surname',
    ], $block->defaultConfiguration());
  }

  public function testBlockValidateRejectsInvalidInput(): void {
    $block = $this->createBlock();
    $formState = new FormState();
    $formState->setValues([
      'items_per_page' => 0,
      'email_label' => ' ',
      'forename_label' => '',
      'surname_label' => '',
    ]);

    $block->blockValidate([], $formState);

    self::assertCount(4, $formState->getErrors());
  }

  public function testBuildRendersTheUserTableAndPager(): void {
    $provider = $this->createMock(RemoteUserProviderInterface::class);
    $provider->expects($this->once())
      ->method('getPage')
      ->with(1, 10)
      ->willReturn(new RemoteUserPage(
        items: [new RemoteUser('jane@example.com', 'Jane', 'Doe')],
        page: 1,
        perPage: 10,
        total: 1,
        totalPages: 1,
      ));

    $pagerManager = $this->createMock(PagerManagerInterface::class);
    $pagerManager->expects($this->once())
      ->method('findPage')
      ->willReturn(0);
    $pagerManager->expects($this->once())
      ->method('createPager')
      ->with(1, 10)
      ->willReturn(new Pager(1, 10));

    $block = $this->createBlock([], $provider, $pagerManager);

    $build = $block->build();

    self::assertSame(['Email', 'Forename', 'Surname'], $build['table']['#header']);
    self::assertSame([['jane@example.com', 'Jane', 'Doe']], $build['table']['#rows']);
    self::assertSame('pager', $build['pager']['#type']);
  }

  public function testBuildReturnsFallbackMarkupWhenProviderFails(): void {
    $provider = $this->createMock(RemoteUserProviderInterface::class);
    $provider->method('getPage')
      ->willThrowException(new RemoteUserDirectoryException('Nope.'));

    $pagerManager = $this->createMock(PagerManagerInterface::class);
    $pagerManager->method('findPage')->willReturn(0);

    $block = $this->createBlock([], $provider, $pagerManager);

    $build = $block->build();

    self::assertSame('The user list is temporarily unavailable.', $build['#markup']);
  }

  public function testGetCacheMaxAgeMatchesConfiguredTtl(): void {
    $block = $this->createBlock(configValues: [
      'cache_ttl' => 300,
    ]);

    self::assertSame(300, $block->getCacheMaxAge());
  }

  public function testGetCacheContextsVariesByPagerQueryArgument(): void {
    $block = $this->createBlock();

    self::assertContains('url.query_args:page', $block->getCacheContexts());
  }

  public function testGetCacheTagsDependsOnModuleSettings(): void {
    $block = $this->createBlock();

    self::assertContains('config:remote_user_directory.settings', $block->getCacheTags());
  }

  /**
   * @param array<string, mixed> $configuration
   * @param array<string, mixed> $configValues
   */
  private function createBlock(
    array $configuration = [],
    ?RemoteUserProviderInterface $provider = NULL,
    ?PagerManagerInterface $pagerManager = NULL,
    array $configValues = [],
  ): RemoteUserDirectoryBlock {
    $block = new RemoteUserDirectoryBlock(
      $configuration,
      'remote_user_directory_user_list',
      [
        'provider' => 'remote_user_directory',
        'admin_label' => new TranslatableMarkup('Remote user directory'),
      ],
      $provider ?? $this->createMock(RemoteUserProviderInterface::class),
      $pagerManager ?? $this->createMock(PagerManagerInterface::class),
      $this->createConfigFactory($configValues + [
        'cache_ttl' => 300,
      ]),
    );

    $block->setStringTranslation(new IdentityTranslation());
    return $block;
  }
}
