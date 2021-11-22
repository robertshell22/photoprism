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
<p>In order to communicate with the PhotoPrism API, you need to create a PhotoPrism Application, enter in the application parameters below, and have your users connect their PhotoPrism accounts. Follow these steps:</p>
<ol>
  <li>Visit https://dev.photoprism.com/apps/new and follow the steps to create a new application. If you already have an application, skip to the next step.</li>
  <li>Go to https://dev.photoprism.com/apps and click on the name of your application.</li>
  <li>Copy and paste the OAuth 2.0 Client ID and Client Secret into the fields below.</li>
  <li>Save the settings.</li>
  <li>Instruct your users to visit <em>/user/[uid]/photoprism</em> and follow the steps there to connect their PhotoPrism accounts.</li>
  <li>At this point you should be able to build views with the PhotoPrism views module, or otherwise use the services provided if your a module developer basing your code on photoprism module.</li>
</ol>
INSTRUCTIONS;

    $form['instructions'] = [
      '#markup' => $instructions,
    ];

    $form['server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server URL'),
      '#description' => $this->t('Enter the URL to your PhotoPrism server.'),
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

    $form['server_protocol'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use SSL?'),
      '#options' => array(
        'https' => t('Yes'),
      ),
      '#default_value' => $config->get('server_protocol'),
    );

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
