<?php

declare(strict_types=1);

namespace helpers;

use Exception;
use Fossar\GuzzleTranscoder\GuzzleTranscoder;
use GuzzleHttp;
use GuzzleHttp\HandlerStack;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

/**
 * Helper class for web request
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Alexandre Rossi <alexandre.rossi@gmail.com>
 */
class WebClient {
    private Configuration $configuration;
    private ?GuzzleHttp\Client $httpClient = null;
    private Logger $logger;

    public function __construct(Configuration $configuration, Logger $logger) {
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * Provide a HTTP client for use by spouts
     */
    public function getHttpClient(): GuzzleHttp\Client {
        if ($this->httpClient === null) {
            $httpClient = new GuzzleHttp\Client($this->createHttpClientConfig());

            $this->httpClient = $httpClient;
        }

        return $this->httpClient;
    }

    /**
     * Create a config for HTTP client for use by spouts.
     *
     * @return array<string, mixed> $config
     */
    public function createHttpClientConfig(): array {
        $stack = HandlerStack::create();
        $stack->push(new GuzzleTranscoder());

        if ($this->configuration->loggerLevel === Configuration::LOGGER_LEVEL_DEBUG) {
            if ($this->configuration->debug === 0) {
                $logFormat = GuzzleHttp\MessageFormatter::SHORT;
            } elseif ($this->configuration->debug === 1) {
                $logFormat = ">>>>>>>>\n{req_headers}\n<<<<<<<<\n{res_headers}\n--------\n{error}";
            } else {
                $logFormat = GuzzleHttp\MessageFormatter::DEBUG;
            }

            $logger = GuzzleHttp\Middleware::log(
                $this->logger,
                new GuzzleHttp\MessageFormatter($logFormat),
                \Psr\Log\LogLevel::DEBUG
            );
            $stack->push($logger);
        }

        return [
            'allow_redirects' => [
                'track_redirects' => true,
            ],
            'headers' => [
                'User-Agent' => self::getUserAgent(),
            ],
            'handler' => $stack,
            'timeout' => 60, // seconds
            'curl' => [
                // Guzzle will not send Accept-Encoding by default.
                // https://github.com/guzzle/guzzle/pull/3215
                // Delegate choosing compression method to curl.
                \CURLOPT_ENCODING => '',
            ],
        ];
    }

    /**
     * get the user agent to use for web based spouts
     *
     * @param ?string[] $agentInfo
     *
     * @return string the user agent string for this spout
     */
    public function getUserAgent(?array $agentInfo = null): string {
        $userAgent = 'Selfoss/' . SELFOSS_VERSION;

        if ($agentInfo === null) {
            $agentInfo = [];
        }

        $agentInfo[] = '+https://selfoss.aditu.de';

        return $userAgent . ' (' . implode('; ', $agentInfo) . ')';
    }

    /**
     * Retrieve content from url
     *
     * @param ?string $agentInfo Extra user agent info to use in the request
     *
     * @throws GuzzleHttp\Exception\GuzzleException When an error is encountered
     * @throws Exception Unless 200 0K response is received
     */
    public function request(string $url, ?string $agentInfo = null): string {
        $http = $this->getHttpClient();
        $response = $http->get($url);
        $data = (string) $response->getBody();

        if ($response->getStatusCode() !== 200) {
            throw new Exception(substr($data, 0, 512));
        }

        return $data;
    }

    /**
     * Get effective URL of the response.
     * RedirectMiddleware will need to be enabled for this to work.
     *
     * @param string $url requested URL, to use as a fallback
     * @param ResponseInterface $response response to inspect
     *
     * @return string last URL we were redirected to
     */
    public static function getEffectiveUrl(string $url, ResponseInterface $response): string {
        // Sequence of fetched URLs
        $urlStack = array_merge([$url], $response->getHeader(GuzzleHttp\RedirectMiddleware::HISTORY_HEADER));

        return $urlStack[count($urlStack) - 1];
    }
}
