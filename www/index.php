<?php

require __DIR__.'/libs/f3/base.php';

F3::set('DEBUG',0);
F3::set('version','1.3');
F3::set('AUTOLOAD',__dir__.'|libs/f3/|libs/|libs/WideImage/|daos/|libs/twitteroauth|libs/FeedWriter');
F3::set('cache',__dir__.'/data/cache');
F3::set('BASEDIR',__dir__);

// read config
F3::config('config.ini');

// init logger
F3::set(
    'logger', 
    new \helpers\Logger( __dir__.'/data/logs/default.log', F3::get('logger_level') )
);

// init authentication
F3::set('auth', new \helpers\Authentication());

// define js and css files
F3::set('js', array(
    'public/js/jquery-1.8.3.min.js',
    'public/js/lazy-image-loader.js',
    'public/js/spectrum.js',
    'public/js/shortcut-2.01.B.js',
    'public/js/base.js'
));

F3::set('css', array(
    'public/css/spectrum.css',
    'public/css/style.css'
));

// define routes
F3::route('GET /',          'controllers\Index->home');
F3::route('POST /',         'controllers\Index->home');
F3::route('GET /rss',       'controllers\Index->rss');
F3::route('GET /feed',      'controllers\Index->rss');
F3::route('GET /password',  'controllers\Index->password');
F3::route('POST /password', 'controllers\Index->password');
F3::route('GET /update',    'controllers\Items->update');

F3::route('GET /api/login',    'controllers\Api->login');
F3::route('GET /api/logout',    'controllers\Api->logout');
    
if(\F3::get('auth')->isLoggedin()===true) {
    F3::route('POST /mark/@item',    'controllers\Items->mark');
    F3::route('POST /mark',    	 	 'controllers\Items->mark');
    F3::route('POST /unmark/@item',  'controllers\Items->unmark');
    F3::route('POST /starr/@item',   'controllers\Items->starr');
    F3::route('POST /unstarr/@item', 'controllers\Items->unstarr');
    F3::route('GET /source/params', 'controllers\Sources->params');
    F3::route('GET /sources',       'controllers\Sources->show');
    F3::route('GET /source',        'controllers\Sources->add');
    F3::route('POST /source/@id',   'controllers\Sources->write');
    F3::route('POST /source',        'controllers\Sources->write');
    F3::route('DELETE /source/@id', 'controllers\Sources->remove');
    
    F3::route('POST /api/items',         'controllers\Api->items');
    F3::route('GET /api/items',         'controllers\Api->items');
    F3::route('GET /api/mark/@item',    'controllers\Api->mark');
    F3::route('GET /api/starr/@item',   'controllers\Api->starr');
    F3::route('GET /api/unstarr/@item', 'controllers\Api->unstarr');
}

// dispatch
F3::run();