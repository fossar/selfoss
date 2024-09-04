<?php

declare(strict_types=1);

namespace controllers\Helpers;

use helpers\Authentication\AuthenticationService;
use helpers\View;

/**
 * Controller for user related tasks
 */
final class HashPassword {
    private AuthenticationService $authenticationService;
    private View $view;

    public function __construct(AuthenticationService $authenticationService, View $view) {
        $this->authenticationService = $authenticationService;
        $this->view = $view;
    }

    /**
     * password hash generator
     * json
     */
    public function hash(): void {
        $this->authenticationService->ensureIsPrivileged();

        if (!isset($_POST['password'])) {
            $this->view->jsonError([
                'success' => false,
                'error' => 'No password given.',
            ]);
        }

        $this->view->jsonSuccess([
            'success' => true,
            'hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        ]);
    }
}
