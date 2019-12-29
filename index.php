<?php

require __DIR__ . '/src/common.php';

// Load custom language
$lang = $f3->get('language');
if ($lang != '0' && $lang != '') {
    $f3->set('LANGUAGE', $lang);
}

// init authentication
// TODO: remove once we let DI container create database
$f3->set('auth', $f3->get('CONTAINER')(helpers\Authentication::class));

// define routes

// all users
$f3->route('GET /', 'controllers\Index->home'); // html
$f3->route('GET /api/about', 'controllers\Index->about'); // json
$f3->route('GET /password', 'controllers\Index->password'); // html
$f3->route('GET /login', 'controllers\Index->login'); // json
$f3->route('POST /login', 'controllers\Index->login'); // json
$f3->route('GET /logout', 'controllers\Index->logout'); // json
$f3->route('GET /update', 'controllers\Index->update'); // text

// only for loggedin users or on public mode
$f3->route('GET /rss', 'controllers\Rss->rss'); // rss
$f3->route('GET /feed', 'controllers\Rss->rss'); // rss
$f3->route('GET /items', 'controllers\Items->listItems'); // json
$f3->route('GET /tags', 'controllers\Tags->listTags'); // json
$f3->route('GET /tagslist', 'controllers\Tags->tagslist'); // html
$f3->route('GET /stats', 'controllers\Items->stats'); // json
$f3->route('GET /items/sync', 'controllers\Items->sync'); // json
$f3->route('GET /sources/stats', 'controllers\Sources->stats'); // json

// only loggedin users
$f3->route('POST /mark/@item', 'controllers\Items->mark'); // json
$f3->route('POST /mark', 'controllers\Items->mark'); // json
$f3->route('POST /unmark/@item', 'controllers\Items->unmark'); // json
$f3->route('POST /starr/@item', 'controllers\Items->starr'); // json
$f3->route('POST /unstarr/@item', 'controllers\Items->unstarr'); // json
$f3->route('POST /items/sync', 'controllers\Items->updateStatuses'); // json

$f3->route('GET /source/params', 'controllers\Sources->params'); // html
$f3->route('GET /sources', 'controllers\Sources->show'); // html
$f3->route('GET /source', 'controllers\Sources->add'); // html
$f3->route('GET /sources/list', 'controllers\Sources->listSources'); // json
$f3->route('GET /sources/sourcesStats', 'controllers\Sources->sourcesStats'); // json
$f3->route('POST /source/@id', 'controllers\Sources->write'); // json
$f3->route('POST /source', 'controllers\Sources->write'); // json
$f3->route('DELETE /source/@id', 'controllers\Sources->remove'); // json
$f3->route('POST /source/delete/@id', 'controllers\Sources->remove'); // json
$f3->route('POST /source/@id/update', 'controllers\Sources->update'); // json
$f3->route('GET /sources/spouts', 'controllers\Sources->spouts'); // json

$f3->route('POST /tags/color', 'controllers\Tags->color'); // json

$f3->route('GET /opml', 'controllers\Opml->show'); // html
$f3->route('POST /opml', 'controllers\Opml->add'); // json
$f3->route('GET /opmlexport', 'controllers\Opml->export'); // xml

// dispatch
$f3->run();
