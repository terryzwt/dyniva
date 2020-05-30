<?php

namespace Drupal\dyniva_content_access\AccessFilter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\dyniva_content_access\AccessFilterBase;
use Drupal\Core\Session\AccountInterface;

class InternalIPFilter extends AccessFilterBase {

  protected function init(){
    $this->filter_types = [
      'ip',
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function access($filter_type, EntityInterface $entity, AccountInterface $account) {
    $tag = \Drupal::state()->get('dyniva_content_access.ip_header_tag', '');
    $request = $this->request_stack->getCurrentRequest();
    $ip = $request->getClientIp();
    if(!empty($tag) && !empty($request->headers->get($tag))){
      $ip = $request->headers->get($tag);
    }
    if(\Drupal::state()->get('dyniva_content_access.debug', false)){
      \Drupal::logger('dyniva_content_access')->debug("IP: " . $ip);
      \Drupal::logger('dyniva_content_access')->debug("HEADER: " . print_r($request->headers->all(),1));
    }
    return $this->isInternal($ip);
  }

  /**
   * 
   * @param string $ip
   */
  protected  function isInternal($ip){
    $ip = current(explode(', ',$ip));
    $settings = \Drupal::state()->get('dyniva_content_access.internal_ip_range','');
    $ranges = explode("\n",$settings);
    foreach ($ranges as $range){
      $range = trim($range);
      if(!empty($range) && $this->ip_in_range($ip, $range)){
        return true;
      }
    }
    return false;
  }
  /**
   * Check if a given ip is in a network
   * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
   * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
   * @return boolean true if the ip is in this range / false if not.
   */
  protected function ip_in_range( $ip, $range ) {
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      return true;
    }
    if ( strpos( $range, '/' ) == false ) {
      $range .= '/32';
    }
    // $range is in IP/CIDR format eg 127.0.0.1/24
    list( $range, $netmask ) = explode( '/', $range, 2 );
    $range_decimal = ip2long( $range );
    $ip_decimal = ip2long( $ip );
    $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
  }
  /**
   * 
   * @param string $ip
   * @param string $range
   * @return boolean
   */
  protected function ipv6_in_range( $ip, $range ) {
    $default_suffix = '/32';
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      $default_suffix = '/128';
    }
    if ( strpos( $range, '/' ) == false ) {
      $range .= $default_suffix;
    }
    // $range is in IP/CIDR format eg 127.0.0.1/24
    list( $range, $netmask ) = explode( '/', $range, 2 );
    $range_str = inet_pton( $range );
    $ip_str = inet_pton( $ip );
    $binary_range = inet_to_bits($range_str);
    $binary_ip = inet_to_bits($ip_str);
    
    $ip_bits = substr($binary_ip,0,$netmask);
    $range_bits = substr($binary_range,0,$netmask);
    
    return $ip_bits == $range_bits;
  }
  /**
   * 
   * @param string $ip
   * @return string
   */
  public function inet_to_bits($ip)
  {
    $unpacked = unpack('A16', $ip);
    $unpacked = str_split($unpacked[1]);
    $binaryip = '';
    foreach ($unpacked as $char) {
      $binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    return $binaryip;
  }  
}
