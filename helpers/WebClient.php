<?php

namespace helpers;

use Exception;
use Fossar\GuzzleTranscoder\GuzzleTranscoder;
use GuzzleHttp;
use GuzzleHttp\Subscriber\Log\LogSubscriber;

/**
 * Helper class for web request
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class WebClient {
    /** @var GuzzleHttp\Client */
    private static $httpClient;

    /**
     * Provide a HTTP client for use by spouts
     *
     * @return GuzzleHttp\Client
     */
    public static function getHttpClient() {
        if (!isset(self::$httpClient)) {
            $version = \F3::get('version');
            $httpClient = new GuzzleHttp\Client([
                'defaults' => [
                    'headers' => [
                        'User-Agent' => self::getUserAgent(),
                    ]
                ]
            ]);
            $httpClient->getEmitter()->attach(new GuzzleTranscoder());

            if (\F3::get('logger_level') === 'DEBUG') {
                $httpClient->getEmitter()->attach(new LogSubscriber(\F3::get('logger')));
            }

            self::$httpClient = $httpClient;
        }

        return self::$httpClient;
    }

    /**
     * get the user agent to use for web based spouts
     *
     * @return string the user agent string for this spout
     */
    public static function getUserAgent($agentInfo = null) {
        $userAgent = 'Selfoss/' . \F3::get('version');

        if (is_null($agentInfo)) {
            $agentInfo = [];
        }

        $agentInfo[] = '+https://selfoss.aditu.de';

        return $userAgent . ' (' . implode('; ', $agentInfo) . ')';
    }

    /**
     * Retrieve content from url
     *
     * @param string $subagent Extra user agent info to use in the request
     *
     * @throws GuzzleHttp\Exception\RequestException When an error is encountered
     * @throws Exception Unless 200 0K response is received
     *
     * @return string request data
     */
    public static function request($url, $agentInfo = null) {
        $http = self::getHttpClient();
        $response = $http->get($url);
        $data = (string) $response->getBody();

        if ($response->getStatusCode() !== 200) {
            throw new Exception(substr($data, 0, 512));
        }

        return $data;
    }
}
