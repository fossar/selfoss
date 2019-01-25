<?php

namespace helpers;

/**
 * Bridge to make SimplePie fetch resources using Guzzle library
 */
class SimplePieFileGuzzle extends \SimplePie_File {
    public function __construct($url, $timeout = 10, $redirects = 5, $headers = [], $useragent = null, $force_fsockopen = false) {
        $this->url = $url;
        $this->permanent_url = $url;
        $this->useragent = $useragent;

        if (preg_match('/^https?:\/\//i', $url)) {
            $this->method = SIMPLEPIE_FILE_SOURCE_REMOTE | SIMPLEPIE_FILE_SOURCE_CURL;

            $client = \helpers\WebClient::getHttpClient();
            try {
                $response = $client->get($url, [
                    'allow_redirects' => [
                        'max' => $redirects,
                    ],
                    'headers' => [
                        'User-Agent' => $useragent,
                        'Referer' => $url,
                    ] + $headers,
                    'timeout' => $timeout,
                    'connect_timeout' => $timeout,
                    'allow_redirects' => [
                        'track_redirects' => true,
                    ],
                ]);

                $this->headers = $response->getHeaders();
                // Sequence of fetched URLs
                $urlStack = [$url] + $response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER);
                $this->url = $urlStack[count($urlStack) - 1];
                $this->body = (string) $response->getBody();
                $this->status_code = $response->getStatusCode();
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $this->error = $e->getMessage();
                $this->success = false;
            }
        } else {
            $this->method = SIMPLEPIE_FILE_SOURCE_LOCAL | SIMPLEPIE_FILE_SOURCE_FILE_GET_CONTENTS;
            if (empty($url) || !($this->body = trim(file_get_contents($url)))) {
                $this->error = 'file_get_contents could not read the file';
                $this->success = false;
            }
        }
    }
}
