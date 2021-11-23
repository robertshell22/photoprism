<?php

namespace Drupal\photoprism\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ApplicationSettings.
 *
 * @package Drupal\photoprism\Form
 */
class ApplicationSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photoprism_application_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['photoprism.application_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('photoprism.application_settings');

    $instructions = <<<INSTRUCTIONS
<p>In order to communicate with the PhotoPrism API, you need to provide a username and password as well as the full API url.</p>
INSTRUCTIONS;

    $form['instructions'] = [
      '#markup' => $instructions,
    ];

    $form['server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PhotoPrism Server API URL'),
      '#description' => $this->t('Enter the URL to your PhotoPrism server\'s API. (ex. https://server.com/api/v1)'),
      '#default_value' => $config->get('server_url'),
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Enter the PhotoPrism admin username.'),
      '#default_value' => $config->get('username'),
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Enter the PhotoPrism admin password.'),
      '#default_value' => $config->get('password'),
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('photoprism.application_settings')
      ->set('server_url', $form_state->getValue('server_url'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
