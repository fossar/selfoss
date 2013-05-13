<?php

$f3 = require(__DIR__.'/libs/f3/base.php');

$f3->set('DEBUG',0);
$f3->set('version','2.8-SNAPSHOT');
$f3->set('AUTOLOAD',__dir__.'/;libs/f3/;libs/;libs/WideImage/;daos/;libs/twitteroauth/;libs/FeedWriter/');
$f3->set('cache',__dir__.'/data/cache');
$f3->set('BASEDIR',__dir__);
$f3->set('LOCALES',__dir__.'/public/lang/'); 

// read defaults
$f3->config('defaults.ini');

// read config, if it exists
if(file_exists('config.ini'))
    $f3->config('config.ini');

// Load custom language
$f3->set('LANGUAGE',$f3->get('language'));

// init logger
$f3->set(
    'logger', 
    new \helpers\Logger( __dir__.'/data/logs/default.log', $f3->get('logger_level') )
);

// init authentication
$f3->set('auth', new \helpers\Authentication());

// define js files
$f3->set('js', array(
    'public/js/jquery-1.8.3.min.js',
    'public/js/jquery-ui-1.10.0.custom.js',
    'public/js/jquery.mCustomScrollbar.min.js',
    'public/js/jquery.mousewheel.js',
    'public/js/lazy-image-loader.js',
    'public/js/color-by-brightness.js',
    'public/js/spectrum.js',
    'public/js/jquery.hotkeys.js',
    'public/js/selfoss-base.js',
    'public/js/selfoss-events.js',
    'public/js/selfoss-events-navigation.js',
    'public/js/selfoss-events-search.js',
    'public/js/selfoss-events-entries.js',
    'public/js/selfoss-events-entriestoolbar.js',
    'public/js/selfoss-events-sources.js',
    'public/js/selfoss-shortcuts.js'
));

// define css files
$css = array(
    'public/css/jquery.mCustomScrollbar.css',
    'public/css/spectrum.css',
    'public/css/reset.css',
    'public/css/style.css'
);
if(file_exists("user.css"))
    $css[] = "user.css";
$f3->set('css', $css);


// define routes

// all users
$f3->route('GET /',           'controllers\Index->home');     // html
$f3->route('POST /',          'controllers\Index->home');     // html
$f3->route('GET /password',   'controllers\Index->password'); // html
$f3->route('POST /password',  'controllers\Index->password'); // html
$f3->route('GET /login',      'controllers\Index->login');    // json
$f3->route('GET /logout',     'controllers\Index->logout');   // json
$f3->route('GET /update',     'controllers\Index->update');   // text

// only for loggedin users or on public mode
if($f3->get('auth')->isLoggedin()===true || \F3::get('public')==1) {
    $f3->route('GET /rss',       'controllers\Rss->rss');       // rss
    $f3->route('GET /feed',      'controllers\Rss->rss');       // rss
    $f3->route('GET /tags',      'controllers\Tags->tagslist'); // html
}

// only loggedin users
if($f3->get('auth')->isLoggedin()===true) {
    $f3->route('GET  /items',               'controllers\Items->listItems');      // json
    $f3->route('POST /mark/@item',          'controllers\Items->mark');           // json
    $f3->route('POST /mark',                'controllers\Items->mark');           // json
    $f3->route('POST /unmark/@item',        'controllers\Items->unmark');         // json
    $f3->route('POST /starr/@item',         'controllers\Items->starr');          // json
    $f3->route('POST /unstarr/@item',       'controllers\Items->unstarr');        // json
    $f3->route('GET /stats',                'controllers\Items->stats');          // json
    
    $f3->route('GET    /source/params',     'controllers\Sources->params');       // html
    $f3->route('GET    /sources',           'controllers\Sources->show');         // html
    $f3->route('GET    /source',            'controllers\Sources->add');          // html
    $f3->route('GET    /sources/list',      'controllers\Sources->listSources');  // json
    $f3->route('POST   /source/@id',        'controllers\Sources->write');        // json
    $f3->route('POST   /source',            'controllers\Sources->write');        // json
    $f3->route('DELETE /source/@id',        'controllers\Sources->remove');       // json
    $f3->route('POST   /source/delete/@id', 'controllers\Sources->remove');       // json
    $f3->route('GET    /sources/spouts',    'controllers\Sources->spouts');       // json
    $f3->route('GET    /sources/stats',     'controllers\Sources->stats');        // json
    
    $f3->route('GET  /tags',                'controllers\Tags->listTags');        // json
    $f3->route('GET  /tagslist',            'controllers\Tags->tagslist');        // html
    $f3->route('POST /tags/color',          'controllers\Tags->color');           // json
    
    $f3->route('GET  /opml',                'controllers\Opml->show');            // html
    $f3->route('POST /opml',                'controllers\Opml->add');             // html
    $f3->route('GET  /opmlexport',          'controllers\Opml->export');          // xml
}

// dispatch
$f3->run();
