<?php

namespace controllers;

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

    /** @var \controllers\Sources sources controller */
    private $sourcesController;

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

    public function __construct(Authentication $authentication, \daos\Items $itemsDao, Sources $sourcesController, \daos\Sources $sourcesDao, Tags $tagsController, \daos\Tags $tagsDao, View $view, ViewHelper $viewHelper) {
        $this->authentication = $authentication;
        $this->itemsDao = $itemsDao;
        $this->sourcesController = $sourcesController;
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
                header('Content-type: text/plain');
                echo 'Please build the assets using `npm run build` or obtain a pre-built packages from https://selfoss.aditu.de.';
                exit;
            }

            // show as full html page
            header('Content-type: text/html');
            readfile($home);

            return;
        }

        $this->authentication->needsLoggedInOrPublicMode();

        // load tags
        $tags = $this->tagsDao->getWithUnread();

        // load items
        $items = $this->loadItems($options, $tags);

        // load stats
        $stats = $this->itemsDao->stats();
        $this->view->statsAll = $stats['total'];
        $this->view->statsUnread = $stats['unread'];
        $this->view->statsStarred = $stats['starred'];

        foreach ($tags as $tag) {
            if (strpos($tag['tag'], '#') !== 0) {
                continue;
            }
            $this->view->statsUnread -= $tag['unread'];
        }

        $result = [
            'lastUpdate' => \helpers\ViewHelper::date_iso8601($this->itemsDao->lastUpdate()),
            'hasMore' => $items['hasMore'],
            'entries' => $items['entries'],
            'all' => $this->view->statsAll,
            'unread' => $this->view->statsUnread,
            'starred' => $this->view->statsStarred,
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
