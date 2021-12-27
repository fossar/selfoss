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
        header('Content-type: text/plain');

        // only allow access for localhost and loggedin users
        if (!$this->authentication->allowedToUpdate()) {
            exit('unallowed access');
        }

        // update all feeds
        $this->contentLoader->update();

        echo 'finished';
    }

    /**
     * update a single source
     * text
     *
     * @param int $id id of source to update
     *
     * @return void
     */
    public function update($id) {
        header('Content-type: text/plain');

        // only allow access for localhost and authenticated users
        if (!$this->authentication->allowedToUpdate()) {
            exit('unallowed access');
        }

        // update the feed
        $this->contentLoader->updateSingle($id);
        echo 'finished';
    }
}
