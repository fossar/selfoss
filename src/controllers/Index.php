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
            echo $this->view->render('src/templates/home.phtml');

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
     * Provide information about the selfoss instance.
     * json
     *
     * @return void
     */
    public function about() {
        $anonymizer = \helpers\Anonymizer::getAnonymizer();
        $wallabag = !empty(\F3::get('wallabag')) ? [
            'url' => \F3::get('wallabag'), // string
            'version' => \F3::get('wallabag_version'), // int
        ] : null;

        $configuration = [
            'version' => \F3::get('version'),
            'apiversion' => \F3::get('apiversion'),
            'configuration' => [
                'homepage' => \F3::get('homepage') ? \F3::get('homepage') : 'newest', // string
                'anonymizer' => $anonymizer === '' ? null : $anonymizer, // ?string
                'share' => (string) \F3::get('share'), // string
                'wallabag' => $wallabag, // ?array
                'wordpress' => \F3::get('wordpress'), // ?string
                'autoMarkAsRead' => \F3::get('auto_mark_as_read') == 1, // bool
                'autoCollapse' => \F3::get('auto_collapse') == 1, // bool
                'autoStreamMore' => \F3::get('auto_stream_more') == 1, // bool
                'loadImagesOnMobile' => \F3::get('load_images_on_mobile') == 1, // bool
                'itemsPerPage' => \F3::get('items_perpage'), // int
                'unreadOrder' => \F3::get('unread_order'), // string
                'autoHideReadOnMobile' => \F3::get('auto_hide_read_on_mobile') == 1, // bool
                'scrollToArticleHeader' => \F3::get('scroll_to_article_header') == 1, // bool
                'htmlTitle' => trim(\F3::get('html_title')), // string
                'allowPublicUpdate' => \F3::get('allow_public_update_access') == 1, // bool
                'publicMode' => \F3::get('public') == 1, // bool
                'authEnabled' => \F3::get('auth')->enabled() === true, // bool
            ],
        ];

        echo $this->view->jsonSuccess($configuration);
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
        echo $this->view->render('src/templates/hashpassword.phtml');
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
            $itemsHtml .= $this->view->render('src/templates/item.phtml');
        }

        return [
            'html' => $itemsHtml,
            'hasMore' => $itemDao->hasMore()
        ];
    }
}
