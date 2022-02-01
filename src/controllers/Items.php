<?php

namespace controllers;

use daos\ItemOptions;
use helpers\Authentication;
use helpers\View;

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
     * @param ?int $itemId ID of item to mark as read
     *
     * @return void
     */
    public function mark($itemId = null) {
        $this->authentication->needsLoggedIn();

        $lastid = null;
        if ($itemId !== null) {
            $lastid = $itemId;
        } else {
            $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
            if (strpos($contentType, 'application/json') === 0) {
                $body = file_get_contents('php://input');
                $lastid = json_decode($body, true);
            } elseif (isset($_POST['ids'])) {
                $lastid = $_POST['ids'];
            }
        }

        // validate id or ids
        if (!$this->itemsDao->isValid('id', $lastid)) {
            $this->view->error('invalid id');
        }

        $this->itemsDao->mark($lastid);

        $return = [
            'success' => true,
        ];

        $this->view->jsonSuccess($return);
    }

    /**
     * mark item as unread
     * json
     *
     * @param int $itemId id of an item to mark as unread
     *
     * @return void
     */
    public function unmark($itemId) {
        $this->authentication->needsLoggedIn();

        if (!$this->itemsDao->isValid('id', $itemId)) {
            $this->view->error('invalid id');
        }

        $this->itemsDao->unmark($itemId);

        $this->view->jsonSuccess([
            'success' => true,
        ]);
    }

    /**
     * starr item
     * json
     *
     * @param int $itemId id of an item to starr
     *
     * @return void
     */
    public function starr($itemId) {
        $this->authentication->needsLoggedIn();

        if (!$this->itemsDao->isValid('id', $itemId)) {
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
     * @param int $itemId id of an item to unstarr
     *
     * @return void
     */
    public function unstarr($itemId) {
        $this->authentication->needsLoggedIn();

        if (!$this->itemsDao->isValid('id', $itemId)) {
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
     *
     * @return void
     */
    public function listItems() {
        $this->authentication->needsLoggedInOrPublicMode();

        // parse params
        $options = ItemOptions::fromUser($_GET);

        // get items
        $items = $this->itemsDao->get($options);

        $items = array_map(function(array $item) {
            $stringifiedDates = [
                'datetime' => $item['datetime']->format(\DateTime::ATOM),
            ];
            if (!empty($item['updatetime'])) {
                $stringifiedDates['updatetime'] = $item['updatetime']->format(\DateTime::ATOM);
            }

            return array_merge($item, $stringifiedDates);
        }, $items);

        $this->view->jsonSuccess($items);
    }
}
