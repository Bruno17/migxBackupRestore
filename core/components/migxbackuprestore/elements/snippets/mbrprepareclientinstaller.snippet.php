<?php
$v = $modx->getVersionData();
$modx_version='revo' . $modx->getOption('full_version',$v,'');

$modx->setPlaceholder('modx_version',$modx_version);
$modx->setPlaceholder('client_migxbackuprestore.masterlogindata',$modx->getOption('client_migxbackuprestore.masterlogindata'));

$ip = $modx->getOption('ip',$_GET,get_client_ip());
$modx->setPlaceholder('client_ip',$ip);

$clientversion = $modx->getOption('modxversion',$_GET,'');
$modx->setPlaceholder('client_modx_version',$clientversion);

$mode = $modx->getOption('mode',$_GET,'');
$modx->setPlaceholder('mode',$mode);

 function get_client_ip()
 {
      $ipaddress = '';
      if (getenv('HTTP_CLIENT_IP'))
          $ipaddress = getenv('HTTP_CLIENT_IP');
      else if(getenv('HTTP_X_FORWARDED_FOR'))
          $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
      else if(getenv('HTTP_X_FORWARDED'))
          $ipaddress = getenv('HTTP_X_FORWARDED');
      else if(getenv('HTTP_FORWARDED_FOR'))
          $ipaddress = getenv('HTTP_FORWARDED_FOR');
      else if(getenv('HTTP_FORWARDED'))
          $ipaddress = getenv('HTTP_FORWARDED');
      else if(getenv('REMOTE_ADDR'))
          $ipaddress = getenv('REMOTE_ADDR');
      else
          $ipaddress = 'UNKNOWN';

      return $ipaddress;
 }