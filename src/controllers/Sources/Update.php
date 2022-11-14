<?php

declare(strict_types=1);

namespace controllers\Sources;

use helpers\Authentication;
use helpers\ContentLoader;
use helpers\Misc;
use helpers\View;
use InvalidArgumentException;

/**
 * Controller updating sources
 */
class Update {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var ContentLoader content loader */
    private $contentLoader;

    /** @var View view helper */
    private $view;

    public function __construct(
        Authentication $authentication,
        ContentLoader $contentLoader,
        View $view
    ) {
        $this->authentication = $authentication;
        $this->contentLoader = $contentLoader;
        $this->view = $view;
    }

    /**
     * update all feeds
     * text
     */
    public function updateAll(): void {
        // only allow access for localhost and loggedin users
        if (!$this->authentication->allowedToUpdate()) {
            header('HTTP/1.0 403 Forbidden');
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
     * @param string $id id of source to update
     */
    public function update(string $id): void {
        // only allow access for localhost and authenticated users
        if (!$this->authentication->allowedToUpdate()) {
            header('HTTP/1.0 403 Forbidden');
            exit('unallowed access');
        }

        try {
            $id = Misc::forceId($id);
        } catch (InvalidArgumentException $e) {
            $this->view->error('invalid id given');
        }

        // update the feed
        $this->contentLoader->updateSingle($id);
        echo 'finished';
    }
}
