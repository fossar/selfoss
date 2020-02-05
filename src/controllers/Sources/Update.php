<?php

namespace controllers\Sources;

use Base;
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

    /**
     * update a single source
     * text
     *
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function update(Base $f3, array $params) {
        $id = $params['id'];

        // only allow access for localhost and authenticated users
        if (!$this->authentication->allowedToUpdate()) {
            die('unallowed access');
        }

        // update the feed
        $this->contentLoader->updateSingle($id);
        echo 'finished';
    }
}
