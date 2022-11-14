<?php

declare(strict_types=1);

namespace controllers\Opml;

use helpers\Authentication;

/**
 * OPML import form page
 *
 * @TODO move this into JS client
 */
class ImportPage {
    /** @var Authentication authentication helper */
    private $authentication;

    public function __construct(Authentication $authentication) {
        $this->authentication = $authentication;
    }

    /**
     * Shows a simple html form
     * html
     */
    public function show(): void {
        $this->authentication->needsLoggedIn();
        readfile(BASEDIR . '/public/opml.html');
    }
}
