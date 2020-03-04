<?php

namespace helpers;

use Exception;
use Fossar\GuzzleTranscoder\GuzzleTranscoder;
use GuzzleHttp;
use GuzzleHttp\HandlerStack;
use Monolog\Logger;

/**
 * Helper class for web request
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class WebClient {
    /** @var GuzzleHttp\Client */
    private $httpClient;

    /** @var Logger */
    private $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Provide a HTTP client for use by spouts
     *
     * @return GuzzleHttp\Client
     */
    public function getHttpClient() {
        if (!isset($this->httpClient)) {
            $stack = HandlerStack::create();
            $stack->push(new GuzzleTranscoder());

            if (\F3::get('logger_level') === 'DEBUG') {
                $logger = GuzzleHttp\Middleware::log(
                    $this->logger,
                    new GuzzleHttp\MessageFormatter(\F3::get('DEBUG') != 0 ? GuzzleHttp\MessageFormatter::DEBUG : GuzzleHttp\MessageFormatter::SHORT),
                    \Psr\Log\LogLevel::DEBUG
                );
                $stack->push($logger);
            }

            $httpClient = new GuzzleHttp\Client([
                'headers' => [
                    'User-Agent' => self::getUserAgent(),
                ],
                'handler' => $stack,
                'timeout' => 60, // seconds
            ]);

            $this->httpClient = $httpClient;
        }

        return $this->httpClient;
    }

    /**
     * get the user agent to use for web based spouts
     *
     * @param ?string[] $agentInfo
     *
     * @return string the user agent string for this spout
     */
    public function getUserAgent($agentInfo = null) {
        $userAgent = 'Selfoss/' . \F3::get('version');

        if ($agentInfo === null) {
            $agentInfo = [];
        }

        $agentInfo[] = '+https://selfoss.aditu.de';

        return $userAgent . ' (' . implode('; ', $agentInfo) . ')';
    }

    /**
     * Retrieve content from url
     *
     * @param string $url
     * @param ?string $agentInfo Extra user agent info to use in the request
     *
     * @throws GuzzleHttp\Exception\RequestException When an error is encountered
     * @throws Exception Unless 200 0K response is received
     *
     * @return string request data
     */
    public function request($url, $agentInfo = null) {
        $http = $this->getHttpClient();
        $response = $http->get($url);
        $data = (string) $response->getBody();

        if ($response->getStatusCode() !== 200) {
            throw new Exception(substr($data, 0, 512));
        }

        return $data;
    }
}
