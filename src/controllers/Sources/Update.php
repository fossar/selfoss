<?php

declare(strict_types=1);

namespace controllers\Sources;

use helpers\Authentication;
use helpers\ContentLoader;
use helpers\Misc;
use helpers\UpdateVisitor;
use helpers\View;
use InvalidArgumentException;

/**
 * Controller updating sources
 */
class Update {
    private Authentication $authentication;
    private ContentLoader $contentLoader;
    private View $view;

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

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        // Do not want to bother implementing content negotiation.
        $reportProgress = $accept === 'text/event-stream';

        if ($reportProgress) {
            // Server-sent events inspired response format.
            header('Content-Type: text/event-stream');

            $updateVisitor = new class() implements UpdateVisitor {
                private int $finishedCount = 0;

                public function started(int $count): void {
                    echo "event: started\ndata: {\"count\": {$count}}\n\n";
                }

                public function sourceUpdated(): void {
                    ++$this->finishedCount;
                    echo "event: sourceUpdated\ndata: {\"finishedCount\": {$this->finishedCount}}\n\n";
                }

                public function finished(): void {
                    echo "event: finished\ndata: {}\n\n";
                }
            };
        } else {
            $updateVisitor = new class() implements UpdateVisitor {
                public function started(int $count): void {
                }

                public function sourceUpdated(): void {
                }

                public function finished(): void {
                    echo 'finished';
                }
            };
        }

        // update all feeds
        $this->contentLoader->update($updateVisitor);
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
