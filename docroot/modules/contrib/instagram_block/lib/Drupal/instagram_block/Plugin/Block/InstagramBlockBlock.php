<?php

/**
 * @file
 * Contains \Drupal\instagram_block\Plugin\Block\InstagramBlockBlock.
 */

namespace Drupal\instagram_block\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Guzzle\Http\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Instagram block.
 *
 * @Block(
 *   id = "instagram_block_block",
 *   admin_label = @Translation("Instagram block")
 * )
 */
class InstagramBlockBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \Guzzle\Http\Client
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a InstagramBlockBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Guzzle\Http\Client $http_client
   *   The Guzzle HTTP client.
   * @param ConfigFactory $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Client $http_client, ConfigFactory $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_default_client'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settings() {
    return array(
      'width' => '',
      'height' => '',
      'count' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {

    $form['count'] = array(
      '#type' => 'textfield',
      '#title' => t('Number of images to display.'),
      '#default_value' => $this->configuration['count'],
    );

    $form['width'] = array(
      '#type' => 'textfield',
      '#title' => t('Image width in pixels.'),
      '#default_value' => $this->configuration['width'],
    );

    $form['height'] = array(
      '#type' => 'textfield',
      '#title' => t('Image height in pixels.'),
      '#default_value' => $this->configuration['height'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    if (!form_get_errors()) {
      $this->configuration['count'] = $form_state['values']['count'];
      $this->configuration['width'] = $form_state['values']['width'];
      $this->configuration['height'] = $form_state['values']['height'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Build a render array to return the Instagram Images.
    $build = array();
    $configuration = $this->configFactory->get('instagram_block.settings')->get();

    // If no configuration was saved, don't attempt to build block.
    if (empty($configuration['user_id']) || empty($configuration['access_token'])) {
      return $build;
    }

    // Build url for http request.
    $url = "https://api.instagram.com/v1/users/{$configuration['user_id']}/media/recent/";
    $query = array(
      'access_token' => $configuration['access_token'],
      'count' => $this->configuration['count'],
    );

    // Get the instagram images and decode.
    $result = $this->_fetchData($url, $query);
    $result = $result->json();

    foreach ($result['data'] as $post) {
      $build['children'][$post['id']] = array(
        '#theme' => 'instagram_block_image',
        '#data' => $post,
        '#href' => $post['link'],
        '#src' => $post['images']['thumbnail']['url'],
        '#width' => $this->configuration['width'],
        '#height' => $this->configuration['height'],
      );
    }
    if (!empty($build)) {
      $build['#attached'] = array(
        'css' => array(
          drupal_get_path('module', 'instagram_block') . '/css/block.css'
        ),
      );
    }
    return $build;
  }

  /**
   * Sends a http request to the Instagram API Server
   *
   * @param string $url
   *   URL for http request.
   * @param array $query
   *   Query parameters for the url.
   *
   * @return \Guzzle\Http\Message\Response
   *   The encoded response containing the instagram images.
   */
  protected function _fetchData($url, array $query) {

    $request = $this->httpClient->get($url);
    foreach ($query as $key => $parameter) {
      $request->getQuery()->set($key, $parameter);
    }
    $response = $request->send();

    return $response;
  }

}
