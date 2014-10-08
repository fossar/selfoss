<?php

$f3 = require(__DIR__.'/libs/f3/base.php');

$f3->set('DEBUG',0);
$f3->set('version','2.13-SNAPSHOT');
$f3->set('AUTOLOAD',__dir__.'/;libs/f3/;libs/;libs/WideImage/;daos/;libs/twitteroauth/;libs/FeedWriter/;libs/fulltextrss/content-extractor/;libs/fulltextrss/readability/');
$f3->set('cache',__dir__.'/data/cache');
$f3->set('BASEDIR',__dir__);
$f3->set('LOCALES',__dir__.'/public/lang/');
//If the website is loaded through HTTPS we set the full URL (which does not natively work when using apache2 as back-end and nginx as front-end).
$assets = "http";
  if( (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) && $_SERVER['HTTP_X_FORWARDED_PROTO']=="https" ||
      (isset($_SERVER['HTTP_HTTPS'])) && $_SERVER['HTTP_HTTPS']=="https")
  {
    $assets .= "s";
  } else {
    $assets .= "";
  }
  $assets .= "://".$_SERVER['HTTP_HOST'].'/';
$f3->set('ASSETS', $assets);

// read defaults
$f3->config('defaults.ini');

// read config, if it exists
if(file_exists('config.ini'))
    $f3->config('config.ini');

// init logger
$f3->set(
    'logger',
    new \helpers\Logger( __dir__.'/data/logs/default.log', $f3->get('logger_level') )
);

?>
