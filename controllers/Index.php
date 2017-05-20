<?php

namespace controllers;

use Base;

/**
 * Controller for root
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Index extends BaseController {
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
            // show as full html page
            $this->view->publicMode = \F3::get('public') == 1;
            $this->view->authEnabled = \F3::get('auth')->enabled() === true;
            echo $this->view->render('templates/home.phtml');

            return;
        }

        $this->needsLoggedInOrPublicMode();

        // get search param
        if (isset($options['search']) && strlen($options['search']) > 0) {
            $this->view->search = $options['search'];
        }

        // load tags
        $tagsDao = new \daos\Tags();
        $tags = $tagsDao->getWithUnread();

        // load items
        $items = $this->loadItems($options, $tags);
        $this->view->content = $items['html'];

        // load stats
        $itemsDao = new \daos\Items();
        $stats = $itemsDao->stats();
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
        $tagsController = new \controllers\Tags();
        $this->view->tags = $tagsController->renderTags($tags);

        if (isset($options['sourcesNav']) && $options['sourcesNav'] == 'true') {
            // prepare sources display list
            $sourcesDao = new \daos\Sources();
            $sources = $sourcesDao->getWithUnread();
            $sourcesController = new \controllers\Sources();
            $this->view->sources = $sourcesController->renderSources($sources);
        } else {
            $this->view->sources = '';
        }

        // ajax call = only send entries and statistics not full template
        if ($f3->ajax()) {
            $this->view->jsonSuccess([
                'lastUpdate' => \helpers\ViewHelper::date_iso8601($itemsDao->lastUpdate()),
                'hasMore' => $items['hasMore'],
                'entries' => $this->view->content,
                'all' => $this->view->statsAll,
                'unread' => $this->view->statsUnread,
                'starred' => $this->view->statsStarred,
                'tags' => $this->view->tags,
                'sources' => $this->view->sources
            ]);
        }
    }

    /**
     * password hash generator
     * html
     *
     * @return void
     */
    public function password() {
        $this->view->password = true;
        if (isset($_POST['password'])) {
            $this->view->hash = hash('sha512', \F3::get('salt') . $_POST['password']);
        }
        echo $this->view->render('templates/hashpassword.phtml');
    }

    /**
     * login for api json access
     * json
     *
     * @return void
     */
    public function login() {
        $error = null;

        if (isset($_REQUEST['username'])) {
            $username = $_REQUEST['username'];
        } else {
            $username = '';
            $error = 'no username given';
        }

        if (isset($_REQUEST['password'])) {
            $password = $_REQUEST['password'];
        } else {
            $password = '';
            $error = 'no password given';
        }

        if ($error !== null) {
            $this->view->jsonError([
                'success' => false,
                'error' => $error
            ]);
        }

        if (\F3::get('auth')->login($username, $password)) {
            $this->view->jsonSuccess([
                'success' => true
            ]);
        }

        $this->view->jsonSuccess([
            'success' => false,
            'error' => \F3::get('lang_login_invalid_credentials'),
        ]);
    }

    /**
     * logout for api json access
     * json
     *
     * @return void
     */
    public function logout() {
        \F3::get('auth')->logout();
        $this->view->jsonSuccess([
            'success' => true
        ]);
    }

    /**
     * update feeds
     * text
     *
     * @return void
     */
    public function update() {
        // only allow access for localhost and loggedin users
        if (!$this->allowedToUpdate()) {
            die('unallowed access');
        }

        // update feeds
        $loader = new \helpers\ContentLoader();
        $loader->update();

        echo 'finished';
    }

    /*
    * get the unread number of items for a windows 8 badge
    * notification.
    */
    public function badge() {
        // load stats
        $itemsDao = new \daos\Items();
        $this->view->statsUnread = $itemsDao->numberOfUnread();
        echo $this->view->render('templates/badge.phtml');
    }

    public function win8Notifications() {
        echo $this->view->render('templates/win8-notifications.phtml');
    }

    /**
     * load items
     *
     * @param array $options
     * @param array $tags
     *
     * @return string html with items
     */
    private function loadItems(array $options, array $tags) {
        $itemDao = new \daos\Items();
        $itemsHtml = '';

        $firstPage = $options['offset'] == 0
            && $options['fromId'] == ''
            && $options['fromDatetime'] == '';
        if ($options['source'] && $this->allowedToUpdate() && $firstPage) {
            $itemsHtml = '<button type="button" id="refresh-source" class="refresh-source">' . \F3::get('lang_source_refresh') . '</button>';
        }

        $tagsController = new \controllers\Tags();
        foreach ($itemDao->get($options) as $item) {
            // parse tags and assign tag colors
            $item['tags'] = $tagsController->tagsAddColors($item['tags'], $tags);

            $this->view->item = $item;
            $itemsHtml .= $this->view->render('templates/item.phtml');
        }

        return [
            'html' => $itemsHtml,
            'hasMore' => $itemDao->hasMore()
        ];
    }
}
