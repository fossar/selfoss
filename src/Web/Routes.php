<?php

declare(strict_types=1);

// SPDX-License-Identifier: GPL-3.0-or-later
// SPDX-FileCopyrightText: 2011–2015 Tobias Zeising <tobias.zeising@aditu.de>
// SPDX-FileCopyrightText: 2025 Jan Tojnar <jtojnar@gmail.com>

namespace Selfoss\Web;

use Bramus\Router\Router;
use Psr\Container\ContainerInterface;
use Selfoss\controllers;
use Selfoss\helpers\Configuration;

/**
 * Defines API routes serving as an entry point to the web app.
 */
final class Routes {
    public function __construct(
        private Router $router,
        private ContainerInterface $container,
        private Configuration $configuration,
    ) {
    }

    private function setupRoutes(): void {
        // all users
        $this->router->get('/', function(): void {
            // html/json
            $this->container->get(controllers\Index::class)->home();
        });
        $this->router->get('/api/about', function(): void {
            // json
            $this->container->get(controllers\About::class)->about();
        });
        $this->router->post('/api/private/hash-password', function(): void {
            // json
            $this->container->get(controllers\Helpers\HashPassword::class)->hash();
        });
        $this->router->get('/login', function(): void {
            // json, deprecated
            $this->container->get(controllers\Authentication::class)->login();
        });
        $this->router->post('/login', function(): void {
            // json
            $this->container->get(controllers\Authentication::class)->login();
        });
        $this->router->get('/logout', function(): void {
            // json, deprecated
            $this->container->get(controllers\Authentication::class)->logout();
        });
        $this->router->delete('/api/session/current', function(): void {
            // json
            $this->container->get(controllers\Authentication::class)->logout();
        });
        $this->router->get('/update', function(): void {
            // text
            $this->container->get(controllers\Sources\Update::class)->updateAll();
        });

        // only for loggedin users or on public mode
        $this->router->get('/rss', function(): void {
            // rss
            $this->container->get(controllers\Rss::class)->rss();
        });
        $this->router->get('/feed', function(): void {
            // rss
            $this->container->get(controllers\Rss::class)->rss();
        });
        $this->router->get('/items', function(): void {
            // json
            $this->container->get(controllers\Items::class)->listItems();
        });
        $this->router->get('/tags', function(): void {
            // json
            $this->container->get(controllers\Tags::class)->listTags();
        });
        $this->router->get('/stats', function(): void {
            // json
            $this->container->get(controllers\Items\Stats::class)->stats();
        });
        $this->router->get('/items/sync', function(): void {
            // json
            $this->container->get(controllers\Items\Sync::class)->sync();
        });
        $this->router->get('/sources/stats', function(): void {
            // json
            $this->container->get(controllers\Sources::class)->stats();
        });

        // only loggedin users
        $this->router->post('/mark/([0-9]+)', function(string $itemId): void {
            // json
            $this->container->get(controllers\Items::class)->mark($itemId);
        });
        $this->router->post('/mark', function(): void {
            // json
            $this->container->get(controllers\Items::class)->mark();
        });
        $this->router->post('/unmark/([0-9]+)', function(string $itemId): void {
            // json
            $this->container->get(controllers\Items::class)->unmark($itemId);
        });
        $this->router->post('/starr/([0-9]+)', function(string $itemId): void {
            // json
            $this->container->get(controllers\Items::class)->starr($itemId);
        });
        $this->router->post('/unstarr/([0-9]+)', function(string $itemId): void {
            // json
            $this->container->get(controllers\Items::class)->unstarr($itemId);
        });
        $this->router->post('/items/sync', function(): void {
            // json
            $this->container->get(controllers\Items\Sync::class)->updateStatuses();
        });

        $this->router->get('/source/params', function(): void {
            // json
            $this->container->get(controllers\Sources::class)->params();
        });
        $this->router->get('/sources', function(): void {
            // json
            $this->container->get(controllers\Sources::class)->show();
        });
        $this->router->get('/sources/list', function(): void {
            // json
            $this->container->get(controllers\Sources::class)->listSources();
        });
        $this->router->post('/source/((?:new-)?[0-9]+)', function(string $id): void {
            // json
            $this->container->get(controllers\Sources\Write::class)->write($id);
        });
        $this->router->post('/source', function(): void {
            // json
            $this->container->get(controllers\Sources\Write::class)->write();
        });
        $this->router->delete('/source/([0-9]+)', function(string $id): void {
            // json
            $this->container->get(controllers\Sources::class)->remove($id);
        });
        $this->router->post('/source/delete/([0-9]+)', function(string $id): void {
            // json, deprecated
            $this->container->get(controllers\Sources::class)->remove($id);
        });
        $this->router->post('/source/([0-9]+)/update', function(string $id): void {
            // json
            $this->container->get(controllers\Sources\Update::class)->update($id);
        });
        $this->router->get('/sources/spouts', function(): void {
            // json
            $this->container->get(controllers\Sources::class)->spouts();
        });

        $this->router->post('/tags/color', function(): void {
            // json
            $this->container->get(controllers\Tags::class)->color();
        });

        $this->router->post('/opml', function(): void {
            // json
            $this->container->get(controllers\Opml\Import::class)->add();
        });
        $this->router->get('/opmlexport', function(): void {
            // xml
            $this->container->get(controllers\Opml\Export::class)->export();
        });

        // Client side routes need to be directed to index.html.
        $this->router->get('/sign/in|/opml|/password|/manage/sources(/add)?|/(newest|unread|starred)(/(all|tag-[^/]+|source-[0-9]+)(/[0-9]+)?)?', function(): void {
            // html
            $this->container->get(controllers\Index::class)->home();
        });

        $this->router->set404(function(): void {
            header('HTTP/1.1 404 Not Found');
            echo 'Page not found.';
        });

        if ($this->configuration->baseUrl !== null) {
            $this->router->setBasePath($this->configuration->baseUrl->getPath());
        }
    }

    public function run(): bool {
        $this->setupRoutes();

        // dispatch
        return $this->router->run();
    }
}
