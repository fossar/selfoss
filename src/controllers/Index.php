<?php

declare(strict_types=1);

namespace controllers;

use Bramus\Router\Router;
use daos\ItemOptions;
use helpers\Authentication;
use helpers\StringKeyedArray;
use helpers\View;
use helpers\ViewHelper;

/**
 * Controller for root
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Index {
    private Authentication $authentication;
    private \daos\Items $itemsDao;
    private Router $router;
    private \daos\Sources $sourcesDao;
    private Tags $tagsController;
    private \daos\Tags $tagsDao;
    private View $view;
    private ViewHelper $viewHelper;

    public function __construct(Authentication $authentication, \daos\Items $itemsDao, Router $router, \daos\Sources $sourcesDao, Tags $tagsController, \daos\Tags $tagsDao, View $view, ViewHelper $viewHelper) {
        $this->authentication = $authentication;
        $this->itemsDao = $itemsDao;
        $this->router = $router;
        $this->sourcesDao = $sourcesDao;
        $this->tagsController = $tagsController;
        $this->tagsDao = $tagsDao;
        $this->view = $view;
        $this->viewHelper = $viewHelper;
    }

    /**
     * home site
     * json
     */
    public function home(): void {
        $options = $_GET;

        if (!$this->view->isAjax()) {
            $home = BASEDIR . '/public/index.html';
            if (!file_exists($home) || ($homeData = file_get_contents($home)) === false) { // For PHPStan: Error will be already handled by global error handler.
                http_response_code(500);
                echo 'Please build the client assets using `npm run build` or obtain a pre-built packages from https://selfoss.aditu.de.';
                exit;
            }

            // show as full html page
            echo str_replace('@basePath@', $this->router->getBasePath(), $homeData);

            return;
        }

        $this->authentication->ensureCanRead();

        // load tags
        $tags = $this->tagsDao->getWithUnread();

        // load items
        $items = $this->loadItems($options, $tags);

        // load stats
        $stats = $this->itemsDao->stats();
        $statsAll = $stats['total'];
        $statsUnread = $stats['unread'];
        $statsStarred = $stats['starred'];

        foreach ($tags as $tag) {
            if (!str_starts_with($tag['tag'], '#')) {
                continue;
            }
            $statsUnread -= $tag['unread'];
        }

        $lastUpdate = $this->itemsDao->lastUpdate();
        $result = [
            'lastUpdate' => $lastUpdate !== null ? $lastUpdate->format(\DateTime::ATOM) : null,
            'hasMore' => $items['hasMore'],
            'entries' => $items['entries'],
            'all' => $statsAll,
            'unread' => $statsUnread,
            'starred' => $statsStarred,
            'tags' => $tags,
        ];

        if (isset($options['sourcesNav']) && $options['sourcesNav'] == 'true') {
            // prepare sources display list
            $result['sources'] = $this->sourcesDao->getWithUnread();
        }

        $this->view->jsonSuccess($result);
    }

    /**
     * load items
     *
     * @param array<string, mixed> $params request parameters
     * @param array<array{tag: string, color: string, unread: int}> $tags information about tags
     *
     * @return array{
     *     entries: array<array{
     *         id: int,
     *         title: string,
     *         strippedTitle: string,
     *         content: string,
     *         unread: bool,
     *         starred: bool,
     *         source: int,
     *         thumbnail: string,
     *         icon: string,
     *         uid: string,
     *         link: string,
     *         wordCount: int,
     *         lengthWithoutTags: int,
     *         datetime: string,
     *         updatetime: string,
     *         sourcetitle: string,
     *         author: string,
     *         tags: StringKeyedArray<array{backColor: string, foreColor: string}>,
     *     }>,
     *     hasMore: bool,
     * } html with items
     */
    private function loadItems(array $params, array $tags) {
        $options = new ItemOptions($params);
        $entries = [];
        foreach ($this->itemsDao->get($options) as $item) {
            $entries[] = $this->viewHelper->preprocessEntry($item, $this->tagsController, $tags, $options->search);
        }

        return [
            'entries' => $entries,
            'hasMore' => $this->itemsDao->hasMore(),
        ];
    }
}
