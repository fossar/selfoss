<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;

require __DIR__ . '/src/common.php';

/** @var ContainerInterface $container */
$router = $container->get(Bramus\Router\Router::class);

// define routes

// all users
$router->get('/', function() use ($container): void {
    // json
    $container->get(controllers\Index::class)->home();
});
$router->get('/api/about', function() use ($container): void {
    // json
    $container->get(controllers\About::class)->about();
});
$router->post('/api/private/hash-password', function() use ($container): void {
    // json
    $container->get(controllers\Helpers\HashPassword::class)->hash();
});
$router->get('/login', function() use ($container): void {
    // json, deprecated
    $container->get(controllers\Authentication::class)->login();
});
$router->post('/login', function() use ($container): void {
    // json
    $container->get(controllers\Authentication::class)->login();
});
$router->get('/logout', function() use ($container): void {
    // json, deprecated
    $container->get(controllers\Authentication::class)->logout();
});
$router->delete('/api/session/current', function() use ($container): void {
    // json
    $container->get(controllers\Authentication::class)->logout();
});
$router->get('/update', function() use ($container): void {
    // text
    $container->get(controllers\Sources\Update::class)->updateAll();
});

// only for loggedin users or on public mode
$router->get('/rss', function() use ($container): void {
    // rss
    $container->get(controllers\Rss::class)->rss();
});
$router->get('/feed', function() use ($container): void {
    // rss
    $container->get(controllers\Rss::class)->rss();
});
$router->get('/items', function() use ($container): void {
    // json
    $container->get(controllers\Items::class)->listItems();
});
$router->get('/tags', function() use ($container): void {
    // json
    $container->get(controllers\Tags::class)->listTags();
});
$router->get('/stats', function() use ($container): void {
    // json
    $container->get(controllers\Items\Stats::class)->stats();
});
$router->get('/items/sync', function() use ($container): void {
    // json
    $container->get(controllers\Items\Sync::class)->sync();
});
$router->get('/sources/stats', function() use ($container): void {
    // json
    $container->get(controllers\Sources::class)->stats();
});

// only loggedin users
$router->post('/mark/([0-9]+)', function(string $itemId) use ($container): void {
    // json
    $container->get(controllers\Items::class)->mark($itemId);
});
$router->post('/mark', function() use ($container): void {
    // json
    $container->get(controllers\Items::class)->mark();
});
$router->post('/unmark/([0-9]+)', function(string $itemId) use ($container): void {
    // json
    $container->get(controllers\Items::class)->unmark($itemId);
});
$router->post('/starr/([0-9]+)', function(string $itemId) use ($container): void {
    // json
    $container->get(controllers\Items::class)->starr($itemId);
});
$router->post('/unstarr/([0-9]+)', function(string $itemId) use ($container): void {
    // json
    $container->get(controllers\Items::class)->unstarr($itemId);
});
$router->post('/items/sync', function() use ($container): void {
    // json
    $container->get(controllers\Items\Sync::class)->updateStatuses();
});

$router->get('/source/params', function() use ($container): void {
    // json
    $container->get(controllers\Sources::class)->params();
});
$router->get('/sources', function() use ($container): void {
    // json
    $container->get(controllers\Sources::class)->show();
});
$router->get('/sources/list', function() use ($container): void {
    // json
    $container->get(controllers\Sources::class)->listSources();
});
$router->post('/source/((?:new-)?[0-9]+)', function(string $id) use ($container): void {
    // json
    $container->get(controllers\Sources\Write::class)->write($id);
});
$router->post('/source', function() use ($container): void {
    // json
    $container->get(controllers\Sources\Write::class)->write();
});
$router->delete('/source/([0-9]+)', function(string $id) use ($container): void {
    // json
    $container->get(controllers\Sources::class)->remove($id);
});
$router->post('/source/delete/([0-9]+)', function(string $id) use ($container): void {
    // json, deprecated
    $container->get(controllers\Sources::class)->remove($id);
});
$router->post('/source/([0-9]+)/update', function(string $id) use ($container): void {
    // json
    $container->get(controllers\Sources\Update::class)->update($id);
});
$router->get('/sources/spouts', function() use ($container): void {
    // json
    $container->get(controllers\Sources::class)->spouts();
});

$router->post('/tags/color', function() use ($container): void {
    // json
    $container->get(controllers\Tags::class)->color();
});

$router->post('/opml', function() use ($container): void {
    // json
    $container->get(controllers\Opml\Import::class)->add();
});
$router->get('/opmlexport', function() use ($container): void {
    // xml
    $container->get(controllers\Opml\Export::class)->export();
});

// Client side routes need to be directed to index.html.
$router->get('/sign/in|/opml|/password|/manage/sources(/add)?|/(newest|unread|starred)(/(all|tag-[^/]+|source-[0-9]+)(/[0-9]+)?)?', function() use ($container): void {
    // html
    $container->get(controllers\Index::class)->home();
});

$router->set404(function(): void {
    header('HTTP/1.1 404 Not Found');
    echo 'Page not found.';
});

// dispatch
$router->run();
