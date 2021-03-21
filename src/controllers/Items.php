<?php

namespace controllers;

use Base;
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
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function mark(Base $f3, array $params) {
        $this->authentication->needsLoggedIn();

        if (isset($params['item'])) {
            $lastid = $params['item'];
        } else {
            $headers = \F3::get('HEADERS');
            if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'application/json') === 0) {
                $body = \F3::get('BODY');
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
        $this->authentication->needsLoggedIn();

        $lastid = $params['item'];

        if (!$this->itemsDao->isValid('id', $lastid)) {
            $this->view->error('invalid id');
        }

        $this->itemsDao->unmark($lastid);

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
        $this->authentication->needsLoggedIn();

        $id = $params['item'];

        if (!$this->itemsDao->isValid('id', $id)) {
            $this->view->error('invalid id');
        }

        $this->itemsDao->starr($id);
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
        $this->authentication->needsLoggedIn();

        $id = $params['item'];

        if (!$this->itemsDao->isValid('id', $id)) {
            $this->view->error('invalid id');
        }

        $this->itemsDao->unstarr($id);
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
        $this->authentication->needsLoggedInOrPublicMode();

        // parse params
        $options = [];
        if (count($_GET) > 0) {
            $options = $_GET;
        }

        // get items
        $items = $this->itemsDao->get($options);

        $items = array_map(function($item) {
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
