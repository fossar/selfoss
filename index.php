<?php

require __DIR__.'/libs/f3/base.php';

F3::set('DEBUG',0);
F3::set('version','1.0');
F3::set('AUTOLOAD',__dir__.'|libs/f3/|libs/|libs/WideImage/|models/|libs/twitteroauth|libs/FeedWriter');
F3::set('cache',__dir__.'/data/cache');

// read config
F3::config('config.ini');

// init logger
F3::set(
    'logger', 
    new \helpers\Logger( __dir__.'/data/logs/default.log', F3::get('logger_level') )
);
new \helpers\Authentication();

// init authentication
F3::set('auth', new \helpers\Authentication());

// define routes
F3::route('GET /',       'controllers\Index->home');
F3::route('POST /',      'controllers\Index->home');
F3::route('GET /rss',    'controllers\Index->rss');
F3::route('GET /feed',   'controllers\Index->rss');
F3::route('GET /update', 'controllers\Items->update');
    
if(\F3::get('auth')->isLoggedin()===true) {
    F3::route('GET /mark/@item',    'controllers\Items->mark');
    F3::route('GET /starr/@item',   'controllers\Items->starr');
    F3::route('GET /unstarr/@item', 'controllers\Items->unstarr');
    F3::route('GET /source/params', 'controllers\Sources->params');
    F3::route('GET /sources',       'controllers\Sources->show');
    F3::route('POST /source',       'controllers\Sources->add');
    F3::route('PUT /source/@id',    'controllers\Sources->write');
    F3::route('PUT /source',        'controllers\Sources->write');
    F3::route('DELETE /source/@id', 'controllers\Sources->remove');
}

// dispatch
F3::run();