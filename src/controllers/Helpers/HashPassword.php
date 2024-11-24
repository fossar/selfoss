<?php

declare(strict_types=1);

namespace controllers\Helpers;

use helpers\Authentication;
use helpers\View;

/**
 * Controller for user related tasks
 */
final class HashPassword {
    private Authentication $authentication;
    private View $view;

    public function __construct(Authentication $authentication, View $view) {
        $this->authentication = $authentication;
        $this->view = $view;
    }

    /**
     * password hash generator
     * json
     */
    public function hash(): void {
        $this->authentication->ensureIsPrivileged();

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
