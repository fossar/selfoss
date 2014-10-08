<?php

$f3 = require(__DIR__.'/libs/f3/base.php');

$f3->set('DEBUG',0);
$f3->set('version','2.13-SNAPSHOT');
$f3->set('AUTOLOAD',__dir__.'/;libs/f3/;libs/;libs/WideImage/;daos/;libs/twitteroauth/;libs/FeedWriter/;libs/fulltextrss/content-extractor/;libs/fulltextrss/readability/');
$f3->set('cache',__dir__.'/data/cache');
$f3->set('BASEDIR',__dir__);
$f3->set('LOCALES',__dir__.'/public/lang/');

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
