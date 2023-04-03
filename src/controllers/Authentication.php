<?php

declare(strict_types=1);

namespace controllers;

use helpers;
use helpers\View;

/**
 * Controller for user related tasks
 */
class Authentication {
    private helpers\Authentication $authentication;
    private View $view;

    public function __construct(helpers\Authentication $authentication, View $view) {
        $this->authentication = $authentication;
        $this->view = $view;
    }

    /**
     * login for api json access
     * json
     */
    public function login(): void {
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
                'error' => $error,
            ]);
        }

        if ($this->authentication->login($username, $password)) {
            $this->view->jsonSuccess([
                'success' => true,
            ]);
        }

        $this->view->jsonSuccess([
            'success' => false,
            'error' => 'Wrong username/password',
        ]);
    }

    /**
     * logout for api json access
     * json
     */
    public function logout(): void {
        $this->authentication->logout();
        $this->view->jsonSuccess([
            'success' => true,
        ]);
    }
}
