<?php

namespace controllers;

use Bramus\Router\Router;
use daos\ItemOptions;
use helpers\Authentication;
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
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var \daos\Items items */
    private $itemsDao;

    /** @var Router router */
    private $router;

    /** @var \daos\Sources sources */
    private $sourcesDao;

    /** @var \controllers\Tags tags controller */
    private $tagsController;

    /** @var \daos\Tags tags */
    private $tagsDao;

    /** @var View view helper */
    private $view;

    /** @var ViewHelper */
    private $viewHelper;

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
     *
     * @return void
     */
    public function home() {
        $options = $_GET;

        if (!$this->view->isAjax()) {
            $home = BASEDIR . '/public/index.html';
            if (!file_exists($home)) {
                http_response_code(500);
                echo 'Please build the assets using `npm run build` or obtain a pre-built packages from https://selfoss.aditu.de.';
                exit;
            }

            // show as full html page
            echo str_replace('@basePath@', $this->router->getBasePath(), file_get_contents($home));

            return;
        }

        $this->authentication->needsLoggedInOrPublicMode();

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
            if (strpos($tag['tag'], '#') !== 0) {
                continue;
            }
            $statsUnread -= $tag['unread'];
        }

        $result = [
            'lastUpdate' => \helpers\ViewHelper::date_iso8601($this->itemsDao->lastUpdate()),
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
     * @param array $params request parameters
     * @param array $tags information about tags
     *
     * @return array{entries: array, hasMore: bool} html with items
     */
    private function loadItems(array $params, array $tags) {
        $options = ItemOptions::fromUser($params);
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
