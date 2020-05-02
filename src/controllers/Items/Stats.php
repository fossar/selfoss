<?php

namespace controllers\Items;

use helpers\Authentication;
use helpers\View;

/**
 * Controller for viewing item statistics
 */
class Stats {
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
     * returns current basic stats
     * json
     *
     * @return void
     */
    public function stats() {
        $this->authentication->needsLoggedInOrPublicMode();

        $stats = $this->itemsDao->stats();

        $tags = $this->tagsDao->getWithUnread();

        foreach ($tags as $tag) {
            if (strpos($tag['tag'], '#') !== 0) {
                continue;
            }
            $stats['unread'] -= $tag['unread'];
        }

        if (array_key_exists('tags', $_GET) && $_GET['tags'] == 'true') {
            $stats['tags'] = $tags;
        }
        if (array_key_exists('sources', $_GET) && $_GET['sources'] == 'true') {
            $stats['sources'] = $this->sourcesDao->getWithUnread();
        }

        $this->view->jsonSuccess($stats);
    }
}
