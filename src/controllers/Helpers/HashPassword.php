<?php

namespace controllers\Helpers;

use helpers\View;

/**
 * Controller for user related tasks
 */
final class HashPassword {
    /** @var View view helper */
    private $view;

    public function __construct(View $view) {
        $this->view = $view;
    }

    /**
     * password hash generator
     * json
     *
     * @return void
     */
    public function hash() {
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
