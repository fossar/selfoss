<?php

namespace controllers;

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
     * @return void
     */
    public function home() {
        // check login
        $this->authentication();

        // parse params
        $options = [];
        if (\F3::get('homepage') != '') {
            $options = ['type' => \F3::get('homepage')];
        }

        // use ajax given params?
        if (count($_GET) > 0) {
            $options = $_GET;
        }

        if (!isset($options['ajax'])) {
            // show as full html page
            $this->view->publicMode = \F3::get('auth')->isLoggedin() !== true && \F3::get('public') == 1;
            $this->view->loggedin = \F3::get('auth')->isLoggedin() === true;
            echo $this->view->render('templates/home.phtml');

            return;
        }

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
        if (isset($options['ajax'])) {
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
        $this->view = new \helpers\View();
        $this->view->password = true;
        if (isset($_POST['password'])) {
            $this->view->hash = hash('sha512', \F3::get('salt') . $_POST['password']);
        }
        echo $this->view->render('templates/login.phtml');
    }

    /**
     * check and show login/logout
     * html
     *
     * @return void
     */
    private function authentication() {
        // logout
        if (isset($_GET['logout'])) {
            \F3::get('auth')->logout();
            \F3::reroute($this->view->base);
        }

        // login
        $loginRequired = \F3::get('public') != 1 && \F3::get('auth')->isLoggedin() !== true;
        $showLoginForm = isset($_GET['login']) || $loginRequired;
        if ($showLoginForm) {
            // authenticate?
            if (count($_POST) > 0) {
                if (!isset($_POST['username'])) {
                    $this->view->error = 'no username given';
                } elseif (!isset($_POST['password'])) {
                    $this->view->error = 'no password given';
                } elseif (!\F3::get('auth')->login($_POST['username'], $_POST['password'])) {
                    $this->view->error = 'invalid username/password';
                }
            }

            // show login
            if (count($_POST) === 0 || isset($this->view->error)) {
                die($this->view->render('templates/login.phtml'));
            } else {
                \F3::reroute($this->view->base);
            }
        }
    }

    /**
     * login for api json access
     * json
     *
     * @return void
     */
    public function login() {
        $view = new \helpers\View();
        $username = isset($_REQUEST['username']) ? $_REQUEST['username'] : '';
        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';

        if (\F3::get('auth')->login($username, $password)) {
            $view->jsonSuccess([
                'success' => true
            ]);
        }

        $view->jsonSuccess([
            'success' => false
        ]);
    }

    /**
     * logout for api json access
     * json
     *
     * @return void
     */
    public function logout() {
        $view = new \helpers\View();
        \F3::get('auth')->logout();
        $view->jsonSuccess([
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
     * @return string html with items
     */
    private function loadItems($options, $tags) {
        $tagColors = $this->convertTagsToAssocArray($tags);
        $itemDao = new \daos\Items();
        $itemsHtml = '';

        $firstPage = $options['offset'] == 0
            && $options['fromId'] == ''
            && $options['fromDatetime'] == '';
        if ($options['source'] && $this->allowedToUpdate() && $firstPage) {
            $itemsHtml = '<button type="button" id="refresh-source" class="refresh-source">' . \F3::get('lang_source_refresh') . '</button>';
        }

        foreach ($itemDao->get($options) as $item) {
            // parse tags and assign tag colors
            $itemsTags = explode(',', $item['tags']);
            $item['tags'] = [];
            foreach ($itemsTags as $tag) {
                $tag = trim($tag);
                if (strlen($tag) > 0 && isset($tagColors[$tag])) {
                    $item['tags'][$tag] = $tagColors[$tag];
                }
            }

            $this->view->item = $item;
            $itemsHtml .= $this->view->render('templates/item.phtml');
        }

        return [
            'html' => $itemsHtml,
            'hasMore' => $itemDao->hasMore()
        ];
    }

    /**
     * return tag => color array
     *
     * @param array $tags
     *
     * @return array tag color array
     */
    private function convertTagsToAssocArray($tags) {
        $assocTags = [];
        foreach ($tags as $tag) {
            $assocTags[$tag['tag']]['backColor'] = $tag['color'];
            $assocTags[$tag['tag']]['foreColor'] = \helpers\Color::colorByBrightness($tag['color']);
        }

        return $assocTags;
    }
}
