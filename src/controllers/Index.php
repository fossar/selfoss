<?php

namespace controllers;

use Base;
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

    public function __construct(Authentication $authentication, \daos\Items $itemsDao, Sources $sourcesController, \daos\Sources $sourcesDao, Tags $tagsController, \daos\Tags $tagsDao, View $view) {
        $this->authentication = $authentication;
        $this->itemsDao = $itemsDao;
        $this->sourcesController = $sourcesController;
        $this->sourcesDao = $sourcesDao;
        $this->tagsController = $tagsController;
        $this->tagsDao = $tagsDao;
        $this->view = $view;
    }

    /**
     * home site
     * json
     *
     * @param Base $f3 fatfree base instance
     *
     * @return void
     */
    public function home(Base $f3) {
        $options = $_GET;

        if (!$f3->ajax()) {
            $home = BASEDIR . '/public/index.html';
            if (!file_exists($home)) {
                http_response_code(500);
                echo 'Please build the assets using `npm run build` or obtain a pre-built packages from https://selfoss.aditu.de.';
                exit;
            }

            // show as full html page
            readfile($home);

            return;
        }

        $this->authentication->needsLoggedInOrPublicMode();

        // get search param
        $search = null;
        if (isset($options['search']) && strlen($options['search']) > 0) {
            $search = $options['search'];
        }

        // load tags
        $tags = $this->tagsDao->getWithUnread();

        // load items
        $items = $this->loadItems($options, $tags, $search);

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
            'tags' => $tags
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
     * @param ?string $search optional search query
     *
     * @return array{entries: array, hasMore: bool} html with items
     */
    private function loadItems(array $params, array $tags, $search = null) {
        $entries = [];
        foreach ($this->itemsDao->get($params) as $item) {
            $entries[] = ViewHelper::preprocessEntry($item, $this->tagsController, $tags, $search);
        }

        return [
            'entries' => $entries,
            'hasMore' => $this->itemsDao->hasMore()
        ];
    }
}
