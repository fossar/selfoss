<?php

namespace controllers\Sources;

use helpers\Authentication;
use helpers\ContentLoader;

/**
 * Controller updating sources
 */
class Update {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var ContentLoader content loader */
    private $contentLoader;

    public function __construct(Authentication $authentication, ContentLoader $contentLoader) {
        $this->authentication = $authentication;
        $this->contentLoader = $contentLoader;
    }

    /**
     * update all feeds
     * text
     *
     * @return void
     */
    public function updateAll() {
        // only allow access for localhost and loggedin users
        if (!$this->authentication->allowedToUpdate()) {
            die('unallowed access');
        }

        // update all feeds
        $this->contentLoader->update();

        echo 'finished';
    }
}
