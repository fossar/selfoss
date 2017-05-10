<?php

namespace controllers\Items;

use Base;
use helpers\Authentication;
use helpers\View;

/**
 * Controller for synchronizing item statuses
 */
class Sync {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var \daos\Items items */
    private $itemsDao;

    /** @var \daos\Sources sources */
    private $sourcesDao;

    /** @var \controllers\Tags tags controller */
    private $tagsController;

    /** @var \daos\Tags tags */
    private $tagsDao;

    /** @var View view helper */
    private $view;

    public function __construct(Authentication $authentication, \daos\Items $itemsDao, \daos\Sources $sourcesDao, \controllers\Tags $tagsController, \daos\Tags $tagsDao, View $view) {
        $this->authentication = $authentication;
        $this->itemsDao = $itemsDao;
        $this->sourcesDao = $sourcesDao;
        $this->tagsController = $tagsController;
        $this->tagsDao = $tagsDao;
        $this->view = $view;
    }

    /**
     * returns updated database info (stats, item statuses)
     * json
     *
     * @return void
     */
    public function sync(Base $f3) {
        $this->authentication->needsLoggedInOrPublicMode();

        $params = null;
        if (isset($_GET['since'])) {
            $params = $_GET;
        } elseif (isset($_POST['since'])) {
            $params = $_POST;
        } else {
            $this->view->jsonError(['sync' => 'missing since argument']);
        }

        $since = new \DateTime($params['since']);

        $last_update = new \DateTime($this->itemsDao->lastUpdate());

        $sync = [
            'lastUpdate' => $last_update->format('Y-m-d H:i:s'),
        ];

        $sinceId = 0;
        if (array_key_exists('itemsSinceId', $params)) {
            $sinceId = (int) $params['itemsSinceId'];
            if ($sinceId >= 0) {
                $notBefore = new \DateTime($params['itemsNotBefore']);
                if ($sinceId < 1 || !$notBefore) {
                    $sinceId = $this->itemsDao->lowestIdOfInterest() - 1;
                    // only send 1 day worth of items
                    $notBefore = new \DateTime();
                    $notBefore->sub(new \DateInterval('P1D'));
                }

                $itemsHowMany = $f3->get('items_perpage');
                if (array_key_exists('itemsHowMany', $params)
                    && is_int($params['itemsHowMany'])) {
                    $itemsHowMany = min($params['itemsHowMany'],
                                        2 * $itemsHowMany);
                }

                $sync['newItems'] = [];
                foreach ($this->itemsDao->sync($sinceId, $notBefore, $since, $itemsHowMany)
                         as $newItem) {
                    $newItem['tags'] = $this->tagsController->tagsAddColors(explode(',', $newItem['tags']));
                    $this->view->item = $newItem;

                    $sync['newItems'][] = [
                        'id' => $newItem['id'],
                        'datetime' => \helpers\ViewHelper::date_iso8601($newItem['datetime']),
                        'unread' => $newItem['unread'],
                        'starred' => $newItem['starred'],
                        'html' => $this->view->render('src/templates/item.phtml'),
                        'source' => $newItem['source'],
                        'tags' => array_keys($newItem['tags'])
                    ];
                }
                if ($sync['newItems']) {
                    $sync['lastId'] = $this->itemsDao->lastId();
                } else {
                    unset($sync['newItems']);
                }
            }
        }

        if ($last_update > $since) {
            $sync['stats'] = $this->itemsDao->stats();

            if (array_key_exists('tags', $params) && $_GET['tags'] == 'true') {
                $sync['tagshtml'] = $this->tagsController->renderTags($this->tagsDao->getWithUnread());
            }
            if (array_key_exists('sources', $params) && $_GET['sources'] == 'true') {
                $sync['sources'] = $this->sourcesDao->getWithUnread();
            }

            $wantItemsStatuses = array_key_exists('itemsStatuses', $params) && $params['itemsStatuses'] == 'true';
            if ($wantItemsStatuses) {
                $sync['itemUpdates'] = $this->itemsDao->statuses($since);
            }
        }

        $this->view->jsonSuccess($sync);
    }

    /**
     * Items statuses bulk update.
     *
     * @return void
     */
    public function updateStatuses(Base $f3) {
        $this->authentication->needsLoggedIn();

        if (isset($_POST['updatedStatuses'])
            && is_array($_POST['updatedStatuses'])) {
            $this->itemsDao->bulkStatusUpdate($_POST['updatedStatuses']);
        }

        $this->sync($f3);
    }
}
