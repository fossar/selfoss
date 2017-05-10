<?php

namespace controllers;

use Base;
use helpers\Authentication;
use helpers\View;

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

    public function __construct(Authentication $authentication, \daos\Items $itemsDao, \controllers\Sources $sourcesController, \daos\Sources $sourcesDao, \controllers\Tags $tagsController, \daos\Tags $tagsDao, View $view) {
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
     * html
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
                die;
            }

            // show as full html page
            readfile($home);

            return;
        }

        $this->authentication->needsLoggedInOrPublicMode();

        // get search param
        if (isset($options['search']) && strlen($options['search']) > 0) {
            $this->view->search = $options['search'];
        }

        // load tags
        $tags = $this->tagsDao->getWithUnread();

        // load items
        $items = $this->loadItems($options, $tags);
        $this->view->content = $items['html'];

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

        // prepare tags display list
        $this->view->tags = $this->tagsController->renderTags($tags);

        $result = [
            'lastUpdate' => \helpers\ViewHelper::date_iso8601($this->itemsDao->lastUpdate()),
            'hasMore' => $items['hasMore'],
            'entries' => $this->view->content,
            'all' => $this->view->statsAll,
            'unread' => $this->view->statsUnread,
            'starred' => $this->view->statsStarred,
            'tags' => $this->view->tags
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
     * @return string html with items
     */
    private function loadItems(array $params, array $tags) {
        $itemsHtml = '';

        $firstPage = $params['offset'] == 0
            && $params['fromId'] == ''
            && $params['fromDatetime'] == '';
        if ($params['source'] && $this->authentication->allowedToUpdate() && $firstPage) {
            $itemsHtml = '<button type="button" id="refresh-source" class="refresh-source">' . \F3::get('lang_source_refresh') . '</button>';
        }

        foreach ($this->itemsDao->get($params) as $item) {
            // parse tags and assign tag colors
            $item['tags'] = $this->tagsController->tagsAddColors($item['tags'], $tags);

            $this->view->item = $item;
            $itemsHtml .= $this->view->render('src/templates/item.phtml');
        }

        return [
            'html' => $itemsHtml,
            'hasMore' => $this->itemsDao->hasMore()
        ];
    }
}
