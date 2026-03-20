<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\remote_user_directory\Exception\RemoteUserDirectoryException;
use Drupal\remote_user_directory\Service\RemoteUserProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the remote user directory block.
 */
#[Block(
  id: 'remote_user_directory_user_list',
  admin_label: new TranslatableMarkup('Remote user directory'),
  category: new TranslatableMarkup('Custom'),
)]
final class RemoteUserDirectoryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Creates the block instance.
   *
   * @param array<string, mixed> $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\remote_user_directory\Service\RemoteUserProviderInterface $remoteUserProvider
   *   The remote user provider.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   The pager manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    private readonly RemoteUserProviderInterface $remoteUserProvider,
    private readonly PagerManagerInterface $pagerManager,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates the block from the service container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array<string, mixed> $configuration
   *   The block configuration.
   * @param mixed $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    /** @var \Drupal\remote_user_directory\Service\RemoteUserProviderInterface $remoteUserProvider */
    $remoteUserProvider = $container->get(RemoteUserProviderInterface::class);
    /** @var \Drupal\Core\Pager\PagerManagerInterface $pagerManager */
    $pagerManager = $container->get('pager.manager');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $container->get('config.factory');

    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $remoteUserProvider,
      $pagerManager,
      $configFactory,
    );
  }

  /**
   * Returns the block-specific default configuration.
   *
   * @return array<string, int|string>
   *   The default configuration.
   */
  public function defaultConfiguration(): array {
    return [
      'items_per_page' => 10,
      'email_label' => 'Email',
      'forename_label' => 'Forename',
      'surname_label' => 'Surname',
    ];
  }

  /**
   * Builds the block configuration form.
   *
   * @param array<string, mixed> $form
   *   The current form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array<string, mixed>
   *   The block-specific form elements.
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['items_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Items per page'),
      '#default_value' => $this->configuration['items_per_page'],
      '#min' => 1,
      '#max' => 100,
      '#required' => TRUE,
    ];
    $form['email_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email field label'),
      '#default_value' => $this->configuration['email_label'],
      '#required' => TRUE,
    ];
    $form['forename_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Forename field label'),
      '#default_value' => $this->configuration['forename_label'],
      '#required' => TRUE,
    ];
    $form['surname_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Surname field label'),
      '#default_value' => $this->configuration['surname_label'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * Validates the block configuration form.
   *
   * @param array<string, mixed> $form
   *   The current form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    $itemsPerPage = (int) $form_state->getValue('items_per_page');
    if ($itemsPerPage < 1 || $itemsPerPage > 100) {
      $form_state->setErrorByName('items_per_page', (string) $this->t('Items per page must be between 1 and 100.'));
    }

    foreach (['email_label', 'forename_label', 'surname_label'] as $field) {
      if (trim((string) $form_state->getValue($field)) === '') {
        $form_state->setErrorByName($field, (string) $this->t('This value cannot be empty.'));
      }
    }
  }

  /**
   * Saves the block configuration values.
   *
   * @param array<string, mixed> $form
   *   The current form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['items_per_page'] = (int) $form_state->getValue('items_per_page');
    $this->configuration['email_label'] = trim((string) $form_state->getValue('email_label'));
    $this->configuration['forename_label'] = trim((string) $form_state->getValue('forename_label'));
    $this->configuration['surname_label'] = trim((string) $form_state->getValue('surname_label'));
  }

  /**
   * Builds the render array for the block.
   *
   * @return array<string, mixed>
   *   The rendered block output.
   */
  public function build(): array {
    $itemsPerPage = max(1, (int) $this->configuration['items_per_page']);
    $currentPage = $this->pagerManager->findPage() + 1;

    try {
      $userPage = $this->remoteUserProvider->getPage($currentPage, $itemsPerPage);
      $this->pagerManager->createPager($userPage->total, $itemsPerPage);

      $rows = [];
      foreach ($userPage->items as $user) {
        $rows[] = [
          $user->email,
          $user->forename,
          $user->surname,
        ];
      }

      return [
        'table' => [
          '#type' => 'table',
          '#header' => [
            $this->configuration['email_label'],
            $this->configuration['forename_label'],
            $this->configuration['surname_label'],
          ],
          '#rows' => $rows,
          '#empty' => $this->t('No users available.'),
        ],
        'pager' => [
          '#type' => 'pager',
        ],
      ];
    }
    catch (RemoteUserDirectoryException) {
      return [
        '#markup' => (string) $this->t('The user list is temporarily unavailable.'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return max(
      1,
      (int) $this->configFactory->get('remote_user_directory.settings')->get('cache_ttl'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(
      parent::getCacheContexts(),
      ['url.query_args:page'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(
      parent::getCacheTags(),
      ['config:remote_user_directory.settings'],
    );
  }

}
