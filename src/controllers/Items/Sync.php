<?php

declare(strict_types=1);

namespace controllers\Items;

use helpers\Authentication;
use helpers\Configuration;
use function helpers\json_response;
use helpers\View;
use helpers\ViewHelper;

/**
 * Controller for synchronizing item statuses
 */
class Sync {
    private Authentication $authentication;
    private Configuration $configuration;
    private \daos\Items $itemsDao;
    private \daos\Sources $sourcesDao;
    private \controllers\Tags $tagsController;
    private \daos\Tags $tagsDao;
    private View $view;
    private ViewHelper $viewHelper;

    public function __construct(Authentication $authentication, Configuration $configuration, \daos\Items $itemsDao, \daos\Sources $sourcesDao, \controllers\Tags $tagsController, \daos\Tags $tagsDao, View $view, ViewHelper $viewHelper) {
        $this->authentication = $authentication;
        $this->configuration = $configuration;
        $this->itemsDao = $itemsDao;
        $this->sourcesDao = $sourcesDao;
        $this->tagsController = $tagsController;
        $this->tagsDao = $tagsDao;
        $this->view = $view;
        $this->viewHelper = $viewHelper;
    }

    /**
     * returns updated database info (stats, item statuses)
     * json
     */
    public function sync(): void {
        $this->authentication->needsLoggedInOrPublicMode();

        if (isset($_GET['since'])) {
            $params = $_GET;
        } elseif (isset($_POST['since'])) {
            $params = $_POST;
        } else {
            $this->view->jsonError(['sync' => 'missing since argument']);
        }

        // The client should include a timezone offset but let’s default to UTC in case it does not.
        $since = new \DateTimeImmutable($params['since'], new \DateTimeZone('UTC'));

        $lastUpdate = $this->itemsDao->lastUpdate();

        $sync = [
            'lastUpdate' => $lastUpdate !== null ? $lastUpdate->format(\DateTimeImmutable::ATOM) : null,
        ];

        if (array_key_exists('itemsSinceId', $params)) {
            $sinceId = (int) $params['itemsSinceId'];
            if ($sinceId >= 0) {
                $notBefore = isset($params['itemsNotBefore']) ? new \DateTimeImmutable($params['itemsNotBefore']) : null;
                if ($sinceId === 0 || !$notBefore) {
                    $sinceId = $this->itemsDao->lowestIdOfInterest() - 1;
                    // only send 1 day worth of items
                    $notBefore = new \DateTimeImmutable();
                    $notBefore = $notBefore->sub(new \DateInterval('P1D'));
                }

                $itemsHowMany = $this->configuration->itemsPerpage;
                if (array_key_exists('itemsHowMany', $params)
                    && is_int($params['itemsHowMany'])) {
                    $itemsHowMany = min(
                        $params['itemsHowMany'],
                        2 * $itemsHowMany
                    );
                }

                $sync['newItems'] = function() use ($sinceId, $notBefore, $since, $itemsHowMany) {
                    foreach ($this->itemsDao->sync($sinceId, $notBefore, $since, $itemsHowMany) as $newItem) {
                        yield $this->viewHelper->preprocessEntry($newItem, $this->tagsController);
                    }
                };

                $sync['lastId'] = $this->itemsDao->lastId();
            }
        }

        if ($lastUpdate === null || $lastUpdate > $since) {
            $sync['stats'] = $this->itemsDao->stats();

            if (array_key_exists('tags', $params) && $params['tags'] == 'true') {
                $sync['tags'] = $this->tagsDao->getWithUnread();
            }
            if (array_key_exists('sources', $params) && $params['sources'] == 'true') {
                $sync['sources'] = $this->sourcesDao->getWithUnread();
            }

            $wantItemsStatuses = array_key_exists('itemsStatuses', $params) && $params['itemsStatuses'] == 'true';
            if ($wantItemsStatuses) {
                $sync['itemUpdates'] = $this->itemsDao->statuses($since);
            }
        }

        $this->view->sendResponse(json_response($sync));
    }

    /**
     * Items statuses bulk update.
     */
    public function updateStatuses(): void {
        $this->authentication->needsLoggedIn();

        if (isset($_POST['updatedStatuses'])
            && is_array($_POST['updatedStatuses'])) {
            $this->itemsDao->bulkStatusUpdate($_POST['updatedStatuses']);
        }

        $this->sync();
    }
}
