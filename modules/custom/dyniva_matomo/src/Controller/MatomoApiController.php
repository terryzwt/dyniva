<?php

namespace Drupal\dyniva_matomo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\matomo_reporting_api\MatomoQueryFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class MatomoApiController.
 *
 * @package Drupal\dyniva_matomo\Controller
 */
class MatomoApiController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Matomo query service.
   *
   * @var MatomoQueryFactoryInterface
   */
  protected $matomoQueryFactory;

 /**
  * Constructs.
  * @param MatomoQueryFactoryInterface $matomoQueryFactory
  */
  public function __construct(MatomoQueryFactoryInterface $matomoQueryFactory) {
    $this->matomoQueryFactory = $matomoQueryFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('matomo.query_factory')
    );
  }

  /**
   * Matomo query.
   */
  public function query(Request $request) {
    $method = $request->get('api_method');
    $response = [];
    if($method) {
      $params = $request->get('params',[]);
      $parts = explode('.', $method);
      if($parts[0] == 'Custom'){
        if(method_exists($this, $parts[1])){
          $response = $this->{$parts[1]}($params);
        }
      }elseif(!empty($params['_over_time'])){
        unset($params['_over_time']);
        $response = $this->getApiOverTime($method, $params);
      }else {
        $query = $this->matomoQueryFactory->getQuery($method);
        $query->setParameters($params);
        $response = $query->execute()->getResponse();
      }
    }
//     if(is_array($response) && isset($response[0]->logo)) {
//       global $base_url;
//       $module_path = drupal_get_path('module', 'dyniva_matomo');
//       $prefix = $base_url . '/' . $module_path . '/icons';
//       foreach ($response as &$item) {
//         $item->icon = str_replace('plugins/Morpheus/icons/dist', $prefix, $item->logo);
//       }
//     }
    return new JsonResponse($response);
  }
  /**
   * 
   * @param array $params
   * @return mixed[]|mixed
   */
  public function getEventsData(array $params) {
    $method = $params['_method'];
    $action = $params['_action'];
    unset($params['_method']);
    unset($params['_action']);
    
    $action_id = false;
    $query = $this->matomoQueryFactory->getQuery('Events.getAction');
    $query->setParameters($params);
    $actions = $query->execute()->getResponse();
    foreach ($actions as $item) {
      if($item->label == $action) {
        $action_id = $item->idsubdatatable;
      }
    }
    $response = [];
    if($action_id) {
      $params['idSubtable'] = $action_id;
      if(!empty($params['_over_time'])){
        unset($params['_over_time']);
        $date_range = $this->getDateRange($params);
        $category = [];
        $datas = [];
        foreach ($date_range as $item){
          $params['date'] = $item['date'];
          $query = $this->matomoQueryFactory->getQuery($method);
          $query->setParameters($params);
          $data = $query->execute()->getResponse();
          $convert = ['label' => $item['label']];
          if(!empty($data)) {
            foreach ($data as $cat) {
              $category[$cat->label] = t($cat->label,[],['context' => 'Matomo Event']);
              $convert[$cat->label] = $cat->nb_events;
            }
          }
          $datas[] = $convert;
        }
        foreach ($datas as $index => $item) {
          foreach ($category as $key => $label) {
            if(!isset($item[$key])) {
              $datas[$index][$key] = 0;
            }
          }
        }
        $response['category'] = $category;
        $response['data'] = $datas;
      }else {
        $query = $this->matomoQueryFactory->getQuery($method);
        $query->setParameters($params);
        $response = $query->execute()->getResponse();
      }
    }
    return $response;
  }
  /**
   * Api data over time.
   * 
   * @param string $method
   * @param array $params
   * @return mixed[]
   */
  public function getApiOverTime(string $method, array $params) {
    $date_range = $this->getDateRange($params);
    $data = [];
    foreach ($date_range as $item){
      $params['date'] = $item['date'];
      $query = $this->matomoQueryFactory->getQuery($method);
      $query->setParameters($params);
      $response = $query->execute()->getResponse();
      $response->label = $item['label'];
      $data[] = $response;
    }
    return $data;
  }
  /**
   * 
   * @param array $params
   * @return array
   */
  public static function getDateRange(array $params) {
    $period = $params['period'];
    $date = $params['date'];
    $dateObject = new DrupalDateTime($date);
    $date_range = [];
    switch ($period) {
      case 'day':
        for ($i = 0; $i < 30; $i++) {
          $date_range[] = [
            'label' => $dateObject->format('m-d'),
            'date' => $dateObject->format('Y-m-d'),
          ];
          $dateObject->modify('-1 day');
        }
        break;
      case 'week':
        for ($i = 0; $i < 12; $i++) {
          $date_range[] = [
            'label' => $dateObject->format('W周'),
            'date' => $dateObject->format('Y-m-d'),
          ];
          $dateObject->modify('-1 week');
        }
        break;
      case 'month':
        for ($i = 0; $i < 12; $i++) {
          $date_range[] = [
            'label' => $dateObject->format('Y-m'),
            'date' => $dateObject->format('Y-m-d'),
          ];
          $dateObject->modify('-1 month');
        }
        break;
      case 'year':
        for ($i = 0; $i < 6; $i++) {
          $date_range[] = [
            'label' => $dateObject->format('Y'),
            'date' => $dateObject->format('Y-m-d'),
          ];
          $dateObject->modify('-1 year');
        }
        break;
    }
    $date_range = array_reverse($date_range);
    return $date_range;
  }
}
