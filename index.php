<?php

require __DIR__ . '/src/common.php';

// Load custom language
$lang = $f3->get('language');
if ($lang != '0' && $lang != '') {
    $f3->set('LANGUAGE', $lang);
}

// define routes

// all users
$f3->route('GET /', controllers\Index::class . '->home'); // html
$f3->route('GET /api/about', controllers\About::class . '->about'); // json
$f3->route('GET /password', controllers\Authentication::class . '->password'); // html
$f3->route('GET /login', controllers\Authentication::class . '->login'); // json
$f3->route('POST /login', controllers\Authentication::class . '->login'); // json
$f3->route('GET /logout', controllers\Authentication::class . '->logout'); // json
$f3->route('GET /update', controllers\Sources\Update::class . '->updateAll'); // text

// only for loggedin users or on public mode
$f3->route('GET /rss', controllers\Rss::class . '->rss'); // rss
$f3->route('GET /feed', controllers\Rss::class . '->rss'); // rss
$f3->route('GET /items', controllers\Items::class . '->listItems'); // json
$f3->route('GET /tags', controllers\Tags::class . '->listTags'); // json
$f3->route('GET /tagslist', controllers\Tags::class . '->tagslist'); // html
$f3->route('GET /stats', controllers\Items\Stats::class . '->stats'); // json
$f3->route('GET /items/sync', controllers\Items\Sync::class . '->sync'); // json
$f3->route('GET /sources/stats', controllers\Sources::class . '->stats'); // json

// only loggedin users
$f3->route('POST /mark/@item', controllers\Items::class . '->mark'); // json
$f3->route('POST /mark', controllers\Items::class . '->mark'); // json
$f3->route('POST /unmark/@item', controllers\Items::class . '->unmark'); // json
$f3->route('POST /starr/@item', controllers\Items::class . '->starr'); // json
$f3->route('POST /unstarr/@item', controllers\Items::class . '->unstarr'); // json
$f3->route('POST /items/sync', controllers\Items\Sync::class . '->updateStatuses'); // json

$f3->route('GET /source/params', controllers\Sources::class . '->params'); // html
$f3->route('GET /sources', controllers\Sources::class . '->show'); // html
$f3->route('GET /source', controllers\Sources::class . '->add'); // html
$f3->route('GET /sources/list', controllers\Sources::class . '->listSources'); // json
$f3->route('POST /source/@id', controllers\Sources::class . '\Write->write'); // json
$f3->route('POST /source', controllers\Sources::class . '\Write->write'); // json
$f3->route('DELETE /source/@id', controllers\Sources::class . '->remove'); // json
$f3->route('POST /source/delete/@id', controllers\Sources::class . '->remove'); // json
$f3->route('POST /source/@id/update', controllers\Sources::class . '\Update->update'); // json
$f3->route('GET /sources/spouts', controllers\Sources::class . '->spouts'); // json

$f3->route('POST /tags/color', controllers\Tags::class . '->color'); // json

$f3->route('GET /opml', controllers\Opml\ImportPage::class . '->show'); // html
$f3->route('POST /opml', controllers\Opml\Import::class . '->add'); // json
$f3->route('GET /opmlexport', controllers\Opml\Export::class . '->export'); // xml

// dispatch
$f3->run();
