<?php

namespace controllers;

use Base;

/**
 * Controller for item handling
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Items extends BaseController {
    /**
     * mark items as read. Allows one id or an array of ids
     * json
     *
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function mark(Base $f3, array $params) {
        $this->needsLoggedIn();

        if (isset($params['item'])) {
            $lastid = $params['item'];
        } elseif (isset($_POST['ids'])) {
            $lastid = $_POST['ids'];
        }

        $itemDao = new \daos\Items();

        // validate id or ids
        if (!$itemDao->isValid('id', $lastid)) {
            $this->view->error('invalid id');
        }

        $itemDao->mark($lastid);

        $return = [
            'success' => true
        ];

        $this->view->jsonSuccess($return);
    }

    /**
     * mark item as unread
     * json
     *
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function unmark(Base $f3, array $params) {
        $this->needsLoggedIn();

        $lastid = $params['item'];

        $itemDao = new \daos\Items();

        if (!$itemDao->isValid('id', $lastid)) {
            $this->view->error('invalid id');
        }

        $itemDao->unmark($lastid);

        $this->view->jsonSuccess([
            'success' => true
        ]);
    }

    /**
     * starr item
     * json
     *
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function starr(Base $f3, array $params) {
        $this->needsLoggedIn();

        $id = $params['item'];

        $itemDao = new \daos\Items();

        if (!$itemDao->isValid('id', $id)) {
            $this->view->error('invalid id');
        }

        $itemDao->starr($id);
        $this->view->jsonSuccess([
            'success' => true
        ]);
    }

    /**
     * unstarr item
     * json
     *
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function unstarr(Base $f3, array $params) {
        $this->needsLoggedIn();

        $id = $params['item'];

        $itemDao = new \daos\Items();

        if (!$itemDao->isValid('id', $id)) {
            $this->view->error('invalid id');
        }

        $itemDao->unstarr($id);
        $this->view->jsonSuccess([
            'success' => true
        ]);
    }

    /**
     * returns items as json string
     * json
     *
     * @return void
     */
    public function listItems() {
        $this->needsLoggedInOrPublicMode();

        // parse params
        $options = [];
        if (count($_GET) > 0) {
            $options = $_GET;
        }

        // get items
        $itemDao = new \daos\Items();
        $items = $itemDao->get($options);

        $this->view->jsonSuccess($items);
    }

    /**
     * returns current basic stats
     * json
     *
     * @return void
     */
    public function stats() {
        $this->needsLoggedInOrPublicMode();

        $itemsDao = new \daos\Items();
        $stats = $itemsDao->stats();

        $tagsDao = new \daos\Tags();
        $tags = $tagsDao->getWithUnread();

        foreach ($tags as $tag) {
            if (strpos($tag['tag'], '#') !== 0) {
                continue;
            }
            $stats['unread'] -= $tag['unread'];
        }

        if (array_key_exists('tags', $_GET) && $_GET['tags'] == 'true') {
            $tagsController = new \controllers\Tags();
            $stats['tagshtml'] = $tagsController->renderTags($tags);
        }
        if (array_key_exists('sources', $_GET) && $_GET['sources'] == 'true') {
            $sourcesDao = new \daos\Sources();
            $sourcesController = new \controllers\Sources();
            $stats['sourceshtml'] = $sourcesController->renderSources($sourcesDao->getWithUnread());
        }

        $this->view->jsonSuccess($stats);
    }

    /**
     * returns updated database info (stats, item statuses)
     * json
     *
     * @return void
     */
    public function sync() {
        $this->needsLoggedInOrPublicMode();

        $params = null;
        if (isset($_GET['since'])) {
            $params = $_GET;
        } elseif (isset($_POST['since'])) {
            $params = $_POST;
        } else {
            $this->view->jsonError(['sync' => 'missing since argument']);
        }

        $since = new \DateTime($params['since']);

        $itemsDao = new \daos\Items();
        $last_update = new \DateTime($itemsDao->lastUpdate());

        $sync = [
            'lastUpdate' => $last_update->format(\DateTime::ATOM),
        ];

        $sinceId = 0;
        if (array_key_exists('itemsSinceId', $params)) {
            $sinceId = (int) $params['itemsSinceId'];
            if ($sinceId >= 0) {
                $notBefore = new \DateTime($params['itemsNotBefore']);
                if ($sinceId < 1 || !$notBefore) {
                    $sinceId = $itemsDao->lowestIdOfInterest() - 1;
                    // only send 1 day worth of items
                    $notBefore = new \DateTime();
                    $notBefore->sub(new \DateInterval('P1D'));
                }

                $itemsHowMany = \F3::get('items_perpage');
                if (array_key_exists('itemsHowMany', $params)
                    && is_int($params['itemsHowMany'])) {
                    $itemsHowMany = min($params['itemsHowMany'],
                                        2 * $itemsHowMany);
                }

                $tagsController = new \controllers\Tags();
                $sync['newItems'] = [];
                foreach ($itemsDao->sync($sinceId, $notBefore, $since, $itemsHowMany)
                         as $newItem) {
                    $newItem['tags'] = $tagsController->tagsAddColors(explode(',', $newItem['tags']));
                    $this->view->item = $newItem;

                    $sync['newItems'][] = [
                        'id' => $newItem['id'],
                        'datetime' => \helpers\ViewHelper::date_iso8601($newItem['datetime']),
                        'unread' => $newItem['unread'],
                        'starred' => $newItem['starred'],
                        'html' => $this->view->render('templates/item.phtml'),
                        'source' => $newItem['source'],
                        'tags' => array_keys($newItem['tags'])
                    ];
                }
                if ($sync['newItems']) {
                    $sync['lastId'] = $itemsDao->lastId();
                } else {
                    unset($sync['newItems']);
                }
            }
        }

        if ($last_update > $since) {
            $sync['stats'] = $itemsDao->stats();

            if (array_key_exists('tags', $params) && $_GET['tags'] == 'true') {
                $tagsDao = new \daos\Tags();
                $tagsController = new \controllers\Tags();
                $sync['tagshtml'] = $tagsController->renderTags($tagsDao->getWithUnread());
            }
            if (array_key_exists('sources', $params) && $_GET['sources'] == 'true') {
                $sourcesDao = new \daos\Sources();
                $sourcesController = new \controllers\Sources();
                $sync['sourceshtml'] = $sourcesController->renderSources($sourcesDao->getWithUnread());
            }

            $wantItemsStatuses = array_key_exists('itemsStatuses', $params) && $params['itemsStatuses'] == 'true';
            if ($wantItemsStatuses) {
                $sync['itemUpdates'] = $itemsDao->statuses($since);
            }
        }

        $this->view->jsonSuccess($sync);
    }

    /**
     * Items statuses bulk update.
     *
     * @return void
     */
    public function updateStatuses() {
        $this->needsLoggedIn();

        if (isset($_POST['updatedStatuses'])
            && is_array($_POST['updatedStatuses'])) {
            $itemsDao = new \daos\Items();
            $itemsDao->bulkStatusUpdate($_POST['updatedStatuses']);
        }

        $this->sync($_POST);
    }
}
