<?php

declare(strict_types=1);

namespace controllers;

use daos\ItemOptions;
use helpers\Authentication;
use helpers\Misc;
use helpers\View;
use InvalidArgumentException;

/**
 * Controller for item handling
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Items {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var \daos\Items items */
    private $itemsDao;

    /** @var View view helper */
    private $view;

    public function __construct(Authentication $authentication, \daos\Items $itemsDao, View $view) {
        $this->authentication = $authentication;
        $this->itemsDao = $itemsDao;
        $this->view = $view;
    }

    /**
     * mark items as read. Allows one id or an array of ids
     * json
     *
     * @param ?string $itemId ID of item to mark as read
     */
    public function mark(?string $itemId = null): void {
        $this->authentication->needsLoggedIn();

        $ids = null;
        if ($itemId !== null) {
            $ids = $itemId;
        } else {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') === 0) {
                $body = file_get_contents('php://input');
                $ids = json_decode($body, true);
            } elseif (isset($_POST['ids'])) {
                $ids = $_POST['ids'];
            }
        }

        // validate id or ids
        try {
            $ids = Misc::forceIds($ids);
        } catch (InvalidArgumentException $e) {
            $this->view->error('invalid id');
        }

        $this->itemsDao->mark($ids);

        $return = [
            'success' => true,
        ];

        $this->view->jsonSuccess($return);
    }

    /**
     * mark item as unread
     * json
     *
     * @param string $itemId id of an item to mark as unread
     */
    public function unmark(string $itemId): void {
        $this->authentication->needsLoggedIn();

        try {
            $itemId = Misc::forceId($itemId);
        } catch (InvalidArgumentException $e) {
            $this->view->error('invalid id');
        }

        $this->itemsDao->unmark([(int) $itemId]);

        $this->view->jsonSuccess([
            'success' => true,
        ]);
    }

    /**
     * starr item
     * json
     *
     * @param string $itemId id of an item to starr
     */
    public function starr(string $itemId): void {
        $this->authentication->needsLoggedIn();

        try {
            $itemId = Misc::forceId($itemId);
        } catch (InvalidArgumentException $e) {
            $this->view->error('invalid id');
        }

        $this->itemsDao->starr($itemId);
        $this->view->jsonSuccess([
            'success' => true,
        ]);
    }

    /**
     * unstarr item
     * json
     *
     * @param string $itemId id of an item to unstarr
     */
    public function unstarr(string $itemId): void {
        $this->authentication->needsLoggedIn();

        try {
            $itemId = Misc::forceId($itemId);
        } catch (InvalidArgumentException $e) {
            $this->view->error('invalid id');
        }

        $this->itemsDao->unstarr($itemId);
        $this->view->jsonSuccess([
            'success' => true,
        ]);
    }

    /**
     * returns items as json string
     * json
     */
    public function listItems(): void {
        $this->authentication->needsLoggedInOrPublicMode();

        // parse params
        $options = ItemOptions::fromUser($_GET);

        // get items
        $items = $this->itemsDao->get($options);

        $items = array_map(function(array $item) {
            $stringifiedDates = [
                'datetime' => $item['datetime']->format(\DateTime::ATOM),
                'updatetime' => $item['updatetime']->format(\DateTime::ATOM),
            ];

            return array_merge($item, $stringifiedDates);
        }, $items);

        $this->view->jsonSuccess($items);
    }
}
