<?php

declare(strict_types=1);

namespace Drupal\remote_user_directory\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\remote_user_directory\Service\ExcludedEmailListParser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the admin form for remote API settings.
 */
final class SettingsForm extends ConfigFormBase {

  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    private readonly ExcludedEmailListParser $excludedEmailListParser,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $container->get('config.factory');
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager */
    $typedConfigManager = $container->get('config.typed');
    /** @var \Drupal\remote_user_directory\Service\ExcludedEmailListParser $excludedEmailListParser */
    $excludedEmailListParser = $container->get(ExcludedEmailListParser::class);

    return new self(
      $configFactory,
      $typedConfigManager,
      $excludedEmailListParser,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'remote_user_directory_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @return list<string>
   *   The editable config names.
   */
  protected function getEditableConfigNames(): array {
    return ['remote_user_directory.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array<string, mixed>
   *   The updated form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $excludedEmails = $this->excludedEmailListParser->parse(
      $this->config('remote_user_directory.settings')->get('excluded_emails'),
    );

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ReqRes API key'),
      '#description' => $this->t('Store the key here for local development, or override it via settings.php.'),
      '#config_target' => 'remote_user_directory.settings:api_key',
      '#required' => TRUE,
    ];
    $form['base_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Base API URI'),
      '#config_target' => 'remote_user_directory.settings:base_uri',
      '#required' => TRUE,
    ];
    $form['timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Request timeout'),
      '#description' => $this->t('Timeout in seconds for remote API requests.'),
      '#config_target' => 'remote_user_directory.settings:timeout',
      '#min' => 1,
      '#required' => TRUE,
    ];
    $form['cache_ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache TTL'),
      '#description' => $this->t('Number of seconds to treat cached responses as fresh.'),
      '#config_target' => 'remote_user_directory.settings:cache_ttl',
      '#min' => 1,
      '#required' => TRUE,
    ];
    $form['excluded_emails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded email addresses'),
      '#description' => $this->t('Enter one email address per line to hide matching users from the block output.'),
      '#default_value' => implode(PHP_EOL, $excludedEmails['emails']),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param array<array-key, mixed> $form
   *   The current form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $parsedExcludedEmails = $this->excludedEmailListParser->parse(
      $form_state->getValue('excluded_emails'),
    );
    if ($parsedExcludedEmails['invalidEntries'] !== []) {
      $form_state->setErrorByName('excluded_emails', (string) $this->t(
        'These email addresses are invalid: @emails',
        ['@emails' => implode(', ', $parsedExcludedEmails['invalidEntries'])],
      ));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param array<array-key, mixed> $form
   *   The current form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $parsedExcludedEmails = $this->excludedEmailListParser->parse(
      $form_state->getValue('excluded_emails'),
    );
    $this->configFactory()->getEditable('remote_user_directory.settings')
      ->set('excluded_emails', $parsedExcludedEmails['emails'])
      ->save();
  }

}
