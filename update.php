<?php

chdir(__DIR__);
$f3 = require(__DIR__.'/libs/f3/base.php');

$f3->set('DEBUG',0);
$f3->set( 'AUTOLOAD',__dir__.'/;libs/f3/;libs/;libs/WideImage/;daos/;libs/twitteroauth/;libs/FeedWriter/;libs/content-extractor/;libs/readability/');
$f3->set('cache',__dir__.'/data/cache');
$f3->set('BASEDIR',__dir__);
$f3->set('FTRSS_DATA_DIR', __dir__.'/data/ftrss');

// read config
$f3->config('defaults.ini');
if(file_exists('config.ini')){
    $f3->config('config.ini');
}

// init logger
$f3->set(
    'logger', 
    new \helpers\Logger( __dir__.'/data/logs/default.log', $f3->get('logger_level') )
);

$loader = new \helpers\ContentLoader();
$loader->update();
