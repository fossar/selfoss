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
            // Individual events are short so we need to prevent various layers in the stack from buffering the response body.
            // Otherwise a consuming layer may time out before any output gets to it.

            // Ensure PHP compression is disabled since it would enable output buffering.
            // https://www.php.net/manual/en/zlib.configuration.php#ini.zlib.output-compression
            ini_set('zlib.output_compression', '0');

            // End implicit buffering caused by `output_buffering` option.
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Ask nginx to not buffer FastCGI response.
            // http://nginx.org/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_buffering
            header('X-Accel-Buffering: no');

            // Server-sent events inspired response format.
            header('Content-Type: text/event-stream');

            $updateVisitor = new class implements UpdateVisitor {
                private int $finishedCount = 0;

                private function sendEvent(string $type, string $data = '{}'): void {
                    echo "event: {$type}\ndata: {$data}\n\n";
                    flush();
                }

                public function started(int $count): void {
                    $this->sendEvent('started', "{\"count\": {$count}}");
                }

                public function sourceUpdated(): void {
                    ++$this->finishedCount;
                    $this->sendEvent('sourceUpdated', "{\"finishedCount\": {$this->finishedCount}}");
                }

                public function finished(): void {
                    $this->sendEvent('finished');
                }
            };
        } else {
            $updateVisitor = new class implements UpdateVisitor {
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
