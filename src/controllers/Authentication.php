<?php

declare(strict_types=1);

namespace controllers;

use helpers\Authentication\AuthenticationService;
use helpers\View;

/**
 * Controller for user related tasks
 */
class Authentication {
    public function __construct(
        private AuthenticationService $authenticationService,
        private View $view
    ) {
    }

    /**
     * login for api json access
     * json
     */
    public function login(): void {
        $error = null;

        if (!isset($_REQUEST['username'])) {
            $error = 'no username given';
        }

        if (!isset($_REQUEST['password'])) {
            $error = 'no password given';
        }

        if ($error !== null) {
            $this->view->jsonError([
                'success' => false,
                'error' => $error,
            ]);
        }

        // The function automatically checks the request for credentials.
        if ($this->authenticationService->isPrivileged()) {
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
    public function logout(): never {
        $this->authenticationService->destroy();
        $this->view->jsonSuccess([
            'success' => true,
        ]);
    }
}
