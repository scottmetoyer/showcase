<?php

/**
 * @file
 * Contains \Drupal\instagram_block\Form\InstagramBlockForm.
 */

namespace Drupal\instagram_block\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure instagram_block settings for this site.
 */
class InstagramBlockForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'instagram_block_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // Get module configuration.
    $config = $this->configFactory->get('instagram_block.settings');

    $path = 'https://instagram.com/oauth/authorize/';
    $options = array(
      'query' => array(
        'client_id' => '759ec610e0c1416baa8a8a6b41552087',
        'redirect_uri' => 'http://instagram.yanniboi.com/configure/instagram',
        'response_type' => 'code',
      ),
      'attributes' => array(
        'target' => '_blank',
      ),
    );
    $link = l(t('here'), $path, $options);

    $form['authorise'] = array(
      '#markup' => t('To configure your instagram account you need to authorise your account. To do this, click !link.', array('!link' => $link)),
    );

    $form['user_id'] = array(
      '#type' => 'textfield',
      '#title' => t('User Id'),
      '#description' => t('Your unique Instagram user id. Eg. 460786510'),
      '#default_value' => $config->get('user_id'),
    );

    $form['access_token'] = array(
      '#type' => 'textfield',
      '#title' => t('Access Token'),
      '#description' => t('Your Instagram access token. Eg. 460786509.ab103e5.a54b6834494643588d4217ee986384a8'),
      '#default_value' => $config->get('access_token'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Get module configuration.
    $config = $this->configFactory->get('instagram_block.settings');
    if (isset($form_state['values'])) {
      $config->set('user_id', $form_state['values']['user_id'])
        ->set('access_token', $form_state['values']['access_token'])
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
