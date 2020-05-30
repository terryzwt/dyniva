<?php

namespace Drupal\dyniva_content_moderation;

use JonnyW\PhantomJs\Client;
use JonnyW\PhantomJs\DependencyInjection\ServiceContainer;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\node\NodeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class Capture {

  private $configFactory;
  private $config;
  protected $client;
  protected $cookie;

  public function __construct(ConfigFactoryInterface $config_factory)
  {
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('ccms_capture.settings');
    $this->client = Client::getInstance();
    $binary = $this->config->get('binary');
    if (file_exists($binary)) {
      $this->client->getEngine()->setPath($binary);
    }
    $location = \Drupal::service('file_system')->realpath(drupal_get_path('module', 'dyniva_content_moderation') . '/js');
    $serviceContainer = ServiceContainer::getInstance();
    $procedureLoader = $serviceContainer->get('procedure_loader_factory')
      ->createProcedureLoader($location);
    $this->client->getProcedureLoader()->addLoader($procedureLoader);

    $sm = \Drupal::service('session_manager');
    $params = session_get_cookie_params();
    $this->cookie = $sm->getName() . "=" . $sm->getId() . ";domain=" . $params['domain'] .  "; path=" . $params['path'];

  }

  public function captureNode(NodeInterface $node, $force = FALSE) {
    $url = Url::fromRoute('entity.node.revision', ['node' => $node->id(), 'node_revision' => $node->vid->value], ['absolute' => TRUE])->toString();
    $destination = \Drupal::config('system.file')->get('default_scheme') . '://' . $this->config->get('destination');
    $destination = \Drupal::service('file_system')->realpath($destination . '/' . $node->vid->value . '.jpg');
    $this->capture($url, $destination, $force);
  }

  protected function capture($url, $destination, $force = FALSE) {
    if (!$force && file_exists($destination) || !file_exists($this->client->getEngine()->getPath())) {
      return;
    }

    $request = $this->client->getMessageFactory()->createCaptureRequest($url);
    $request->addHeader('cookie', $this->cookie);
    $request->setBodyStyles(['backgroundColor' => '#fff']);
    $request->setOutputFile($destination);
    $response = $this->client->getMessageFactory()->createResponse();
    $this->client->send($request, $response);
  }

  public function login($uid) {
    $this->client->setProcedure('login');
    $account = User::load($uid);
    $url = user_pass_reset_url($account);
    $request = $this->client->getMessageFactory()->createRequest($url);
    $response = $this->client->getMessageFactory()->createResponse();
    $this->client->send($request, $response);
  }
}
