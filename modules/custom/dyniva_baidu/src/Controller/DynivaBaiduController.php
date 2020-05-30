<?php

namespace Drupal\dyniva_baidu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DynivaBaiduController.
 *
 * @package Drupal\dyniva_baidu\Controller
 */
class DynivaBaiduController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function baiduMap($text) {
    $str = '<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
  <style type="text/css">
    body, html,#allmap {width: 100%;height: 100vh;overflow: hidden;margin:0;font-family:"微软雅黑";}
  </style>
  <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=eTCedh5mCQextXOOZdsr0gfMq946ozf1"></script>
  <title>地址解析</title>
</head>
<body>
  <input id="text" type="hidden" name="text" value="' . $text . '">
  <div id="allmap"></div>
</body>
</html>
<script type="text/javascript">
  var text = document.getElementById("text").value;
  // 百度地图API功能
  var map = new BMap.Map("allmap");
  var point = new BMap.Point(116.331398,39.897445);
  map.centerAndZoom(point,12);
  // 创建地址解析器实例
  var myGeo = new BMap.Geocoder();
  // 将地址解析结果显示在地图上,并调整地图视野
  myGeo.getPoint(text, function(point){
          if (point) {
                  map.centerAndZoom(point, 16);
                  map.addOverlay(new BMap.Marker(point));
          }else{
                  alert("您选择地址没有解析到结果!");
          }
  }, "全国");
</script>';
    $response = new Response($str);
    $response->headers->set('Content-Type', 'text/html; charset=utf-8');

    return $response;

  }

}
