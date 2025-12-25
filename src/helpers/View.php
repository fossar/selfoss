<?php

declare(strict_types=1);

namespace Selfoss\helpers;

use Exception;
use GuzzleHttp\Psr7\Uri;
use function Http\Response\send;
use function json_encode;
use const JSON_ERROR_NONE;
use function json_last_error;
use function json_last_error_msg;
use Psr\Http\Message\ResponseInterface;

/**
 * Helper class for rendering template
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
final class View {
    /** Current base url */
    private ?Uri $baseUrl = null;

    /**
     * set global view vars
     */
    public function __construct(
        private readonly Configuration $configuration
    ) {
    }

    /**
     * Returns the absolute base URL of the page, with path ending with a /.
     *
     * The returned value depends on the value of the `base_url` option in `config.ini`:
     *
     * - unset – implicit base URI automatically determined from global server variables ($_SERVER)
     * - absolute path – the implicit base URI with path replaced with the configured value
     * - absolute URL – the configured value as is
     */
    public function getBaseUrl(): Uri {
        if ($this->baseUrl === null) {
            $this->baseUrl = $this->makeBaseUrl();
        }

        return $this->baseUrl;
    }

    private function makeBaseUrl(): Uri {
        $base = $this->configuration->baseUrl;

        if ($base !== null && Uri::isAbsolute($base)) {
            // base url in config.ini file
            return $base;
        }

        // auto generate base url

        $protocol = 'http';
        if ((isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['HTTP_HTTPS']) && $_SERVER['HTTP_HTTPS'] === 'https')) {
            $protocol = 'https';
        }

        // check for SSL proxy
        if (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && isset($_SERVER['HTTP_X_FORWARDED_HOST'])
        && ($_SERVER['HTTP_X_FORWARDED_SERVER'] === $_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $subdir = '/' . preg_replace('/\/[^\/]+$/', '', $_SERVER['PHP_SELF']);
            $host = $_SERVER['HTTP_X_FORWARDED_SERVER'];
        } else {
            $subdir = '';
            if (PHP_SAPI !== 'cli') {
                $subdir = rtrim(strtr(dirname($_SERVER['SCRIPT_NAME']), '\\', '/'), '/');
            }
            $host = $_SERVER['SERVER_NAME'];
        }

        $port = '';
        if (isset($_SERVER['SERVER_PORT'])
            && (($protocol === 'http' && $_SERVER['SERVER_PORT'] != '80')
                || ($protocol === 'https' && $_SERVER['SERVER_PORT'] != '443'))) {
            $port = ':' . $_SERVER['SERVER_PORT'];
        }
        // Override the port if nginx is the front end and the traffic is being forwarded
        if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $port = ':' . $_SERVER['HTTP_X_FORWARDED_PORT'];
        }

        $base = (new Uri($protocol . '://' . $host . $port))
            ->withPath($base !== null ? $base->getPath() : ($subdir . '/'));

        return $base;
    }

    /**
     * Tests whether the current request was made using AJAX.
     *
     * (The JavaScript AJAX library needs to set the header.)
     */
    public function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * send error message
     */
    public function error(string $message): never {
        header('HTTP/1.0 400 Bad Request');
        exit($message);
    }

    /**
     * send error message as json string
     */
    public function jsonError(mixed $data): never {
        header('Content-type: application/json');

        $error = @json_encode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg(), json_last_error());
        }
        assert($error !== false); // For PHPStan: Exception would be thrown when the function returns false.

        $this->error($error);
    }

    /**
     * send success message as json string
     */
    public function jsonSuccess(mixed $data): never {
        header('Content-type: application/json');

        $message = @json_encode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg(), json_last_error());
        }
        assert($message !== false); // For PHPStan: Exception would be thrown when the function returns false.

        exit($message);
    }

    /**
     * Send a PSR-7 response.
     */
    public function sendResponse(ResponseInterface $response): never {
        send($response);
        exit;
    }
}

/**
 * Create a PSR-7 response for given JSON-encodable data.
 */
function json_response(mixed $data): \GuzzleHttp\Psr7\Response {
    $encoder = new \Violet\StreamingJsonEncoder\BufferJsonEncoder($data);
    $stream = new \Violet\StreamingJsonEncoder\JsonStream($encoder);

    $response = new \GuzzleHttp\Psr7\Response();
    $response = $response->withHeader('Content-type', 'application/json');
    $response = $response->withBody($stream);

    return $response;
}
