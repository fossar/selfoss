<?php

declare(strict_types=1);

namespace Selfoss\controllers\Items;

use Selfoss\helpers\Authentication;
use Selfoss\helpers\View;

/**
 * Controller for viewing item statistics
 */
class Stats {
    private Authentication $authentication;
    private \Selfoss\daos\Items $itemsDao;
    private \Selfoss\daos\Sources $sourcesDao;
    private \Selfoss\daos\Tags $tagsDao;
    private View $view;

    public function __construct(Authentication $authentication, \Selfoss\daos\Items $itemsDao, \Selfoss\daos\Sources $sourcesDao, \Selfoss\daos\Tags $tagsDao, View $view) {
        $this->authentication = $authentication;
        $this->itemsDao = $itemsDao;
        $this->sourcesDao = $sourcesDao;
        $this->tagsDao = $tagsDao;
        $this->view = $view;
    }

    /**
     * returns current basic stats
     * json
     */
    public function stats(): void {
        $this->authentication->ensureCanRead();

        $stats = $this->itemsDao->stats();

        $tags = $this->tagsDao->getWithUnread();

        foreach ($tags as $tag) {
            if (!str_starts_with($tag['tag'], '#')) {
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
