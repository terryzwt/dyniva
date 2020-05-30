<?php

namespace Drupal\dyniva_elastic_search\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\dyniva_elastic_search\SearchHelper;

/**
 * Custom exception subscriber.
 */
class ElasticConnectorSubscriber implements EventSubscriberInterface {

  /**
   * Handles errors for this subscriber.
   *
   * @param \Drupal\elasticsearch_connector\Event\PrepareMappingEvent $event
   *   The event to process.
   */
  public function onPrepareMapping(\Drupal\elasticsearch_connector\Event\PrepareMappingEvent $event) {
    if($event->getMappingType() == 'text') {
      $mappingConfig = $event->getMappingConfig();
      $mappingConfig += [
        "analyzer" => "ik_max_word",
        "search_analyzer" => "ik_smart",
      ];
      $event->setMappingConfig($mappingConfig);
    }
  }
  /**
   * Handles errors for this subscriber.
   *
   * @param \Drupal\elasticsearch_connector\Event\PrepareSearchQueryEvent $event
   *   The event to process.
   */
  public function onPrepareQuery(\Drupal\elasticsearch_connector\Event\PrepareSearchQueryEvent $event) {
    $search_query = $event->getElasticSearchQuery();
    if(!empty($search_query['query_search_string']['query_string']['query'])) {
      $search_string = $search_query['query_search_string']['query_string']['query'];
      $search_string = trim($search_string);
      $search_string = trim($search_string,'~');
      SearchHelper::addQueryLog($search_string, REQUEST_TIME);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[\Drupal\elasticsearch_connector\Event\PrepareMappingEvent::PREPARE_MAPPING] = 'onPrepareMapping';
    $events[\Drupal\elasticsearch_connector\Event\PrepareSearchQueryEvent::PREPARE_QUERY] = 'onPrepareQuery';
    return $events;
  }

}
