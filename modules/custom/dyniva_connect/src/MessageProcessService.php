<?php

namespace Drupal\dyniva_connect;


use Drupal\dyniva_connect\Entity\Connector;

/**
 * Class MessageProcessService.
 *
 * @package Drupal\dyniva_connect
 */
class MessageProcessService implements MessageProcessServiceInterface {
  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * Array of loaded processor services keyed by their ids.
   *
   * @var array
   */
  protected $processors = [];
  
  
  protected $sortedProcessors = NULL;
  
  /**
   * {@inheritdoc}
   */
  public function addProcessor(ConnectorMessageProcessorInterface $processor, $priority = 0) {
    $this->processors[$priority][] = $processor;
    $this->sortedProcessors = NULL;
    return $this;
  }
  
  /**
   * Sorts translators according to priority.
   *
   * @return \Drupal\Core\StringTranslation\Translator\TranslatorInterface[]
   *   A sorted array of translator objects.
   */
  protected function sortProcessors() {
    $sorted = [];
    krsort($this->processors);
    
    foreach ($this->processors as $processors) {
      $sorted = array_merge($sorted, $processors);
    }
    return $sorted;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getProcessors() {
    if ($this->sortedProcessors === NULL) {
      $this->sortedProcessors = $this->sortProcessors();
    }
    return $this->sortedProcessors;
  }
  
  /**
   * {@inheritdoc}
   */
  public function process(Connector $connector, $app) {
    foreach ($this->getProcessors() as $processor){
      if($processor->apply($connector)){
        if($processor->process($connector, $app) === FALSE){
          break;
        }
      }
    }
  }
  
}
