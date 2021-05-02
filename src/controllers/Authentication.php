<?php

namespace controllers;

use helpers;
use helpers\View;

/**
 * Controller for user related tasks
 */
class Authentication {
    /** @var helpers\Authentication authentication helper */
    private $authentication;

    /** @var View view helper */
    private $view;

    public function __construct(helpers\Authentication $authentication, View $view) {
        $this->authentication = $authentication;
        $this->view = $view;
    }

    /**
     * password hash generator
     * html
     *
     * @return void
     */
    public function password() {
        readfile(BASEDIR . '/public/hashpassword.html');
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
        $this->authentication->logout();
        $this->view->jsonSuccess([
            'success' => true,
        ]);
    }
}
