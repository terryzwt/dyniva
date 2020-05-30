<?php

namespace Drupal\dyniva_elastic_search;


/**
 * 
 * @author ziqiang
 * 
 */
class SearchHelper {

  const QUERY_LOG_INDEX = 'query_log';
  const HOT_WORDS_CACHE_ID = 'dyniva_elastic_search.hot_words';
  
  public static $client = null;
  
  /**
   * 
   * @return \nodespark\DESConnector\ClientInterface $client
   */
  public static function getElasticClient() {
    if(empty($client)){
      $elasticsearchCluster = \Drupal::entityTypeManager()->getStorage('elasticsearch_cluster')->loadMultiple();
      if(!empty($elasticsearchCluster)) {
        $elasticsearchCluster = reset($elasticsearchCluster);
        $clientManager = \Drupal::service('elasticsearch_connector.client_manager');
        static::$client = $clientManager->getClientForCluster($elasticsearchCluster);
      }
    }
    return static::$client;
  }
  /**
   * 
   * @param string $index_id
   */
  public static function createIndex($index_id) {
    if($client = self::getElasticClient()) {
      $params = self::index($index_id);
      if(!$client->indices()->exists($params)) {
        $params +=  [
          'body' => [
            'settings' => [
              'number_of_shards' => 5,
              'number_of_replicas' => 1,
            ],
            "mappings"=> [
              "properties" => [
                "keyword" => [
                  "type" => "text",
                  "fields" => [
                    "keyword" => [
                      "ignore_above" => 64,
                      "type" => "keyword"
                    ]
                  ]
                ],
                "add_time" => [
                  "type" => "long"
                ],
                "_language" => [
                  "type" => "keyword"
                ]
              ]
            ]
          ],
        ];
        $client->indices()->create($params);
      }
    }
  }
  /**
   * 
   * @param string $index_id
   */
  public static function deleteIndex($index_id) {
    if($client = self::getElasticClient()) {
      $params = self::index($index_id);
      $client->indices()->delete($params);
    }
  }
  /**
   * 
   * @param unknown $index_id
   * @return string
   */
  public static function getIndexName($index_id) {
    $options = \Drupal::database()->getConnectionOptions();
    $site_database = $options['database'];
    
    return strtolower(preg_replace(
        '/[^A-Za-z0-9_]+/',
        '',
        'elasticsearch_index_' . $site_database . '_' . $index_id
        ));
  }
  /**
   * 
   * @param string $index_id
   * @return string[]
   */
  public static function index($index_id) {
    $params = [];
    $params['index'] = static::getIndexName($index_id);
    return $params;
  }
  /**
   * 
   * @param string $queryString
   * @param int $timestamp
   */
  public static function addQueryLog($queryString, $timestamp) {
    if($client = self::getElasticClient()) {
      self::createIndex(self::QUERY_LOG_INDEX);
      $params = self::index(self::QUERY_LOG_INDEX);
      $params['body'] = [
        'keyword' => $queryString,
        'add_time' => $timestamp,
        '_language' => \Drupal::languageManager()->getCurrentLanguage()->getId()
      ];
      $response = $client->index($params);
    }
  }
  /**
   * 
   * @param string $queryString
   * @param int $timestamp
   */
  public static function getHotWords($limit = 10) {
    $words = [];
    
    $config = \Drupal::config('dyniva_elastic_search.settings');
    $hot_words_count = $config->get('hot_words_count')?:0;
    $hot_words_interval = $config->get('hot_words_interval')?:0;
    $hot_words_cache_enabled = $config->get('hot_words_cache_enabled')?:false;
    $hot_words_cache_interval = ($config->get('hot_words_cache_interval')?:5) * 60 + REQUEST_TIME ;
    $hot_words_black_list = $config->get('hot_words_black_list')?:[];
    
    if($hot_words_cache_enabled && ($cache = \Drupal::cache()->get(self::HOT_WORDS_CACHE_ID))) {
      return $cache->data;
    }
    if($client = self::getElasticClient()) {
      $time = 0;
      if(!empty($hot_words_interval)) {
        $time = REQUEST_TIME - ($hot_words_interval * 24 * 3600);
      }
      
      $params = self::index(self::QUERY_LOG_INDEX);
      $params['body'] = [
        "size" => 0,
        "query" => [
          "bool" => [
            "filter" => [
              ["term" => ['_language' => \Drupal::languageManager()->getCurrentLanguage()->getId()]],
              ["range" => ["add_time" => ["gte" => $time]]],
            ]
          ]
        ],
        "aggs" => [
          "keyword" => [
            "terms" => [
              "field" => "keyword.keyword",
              "size" => $limit,
              "min_doc_count" => $hot_words_count
            ]
          ]
        ]
      ];
      
      foreach ($hot_words_black_list as $item) {
        $params['body']['query']['bool']['must_not'][] = ["match" => ["keyword" => $item]];
      }
      try {
        $response = $client->search($params)->getRawResponse();
        $words = $response['aggregations']['keyword']['buckets'];
        \Drupal::cache()->set(self::HOT_WORDS_CACHE_ID, $words, $hot_words_cache_interval,$config->getCacheTags());
      } catch (\Exception $e) {
      }
    }
    return $words;
  }

}
