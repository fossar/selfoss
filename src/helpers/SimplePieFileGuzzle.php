<?php

namespace helpers;

use SimplePie\File;
use SimplePie\SimplePie;

/**
 * Bridge to make SimplePie fetch resources using Guzzle library
 */
class SimplePieFileGuzzle extends File {
    /** @var WebClient */
    private $webClient;

    /**
     * @param string $url
     * @param int $timeout
     * @param int $redirects
     * @param ?array<string,string> $headers
     * @param ?string $useragent
     * @param bool $force_fsockopen
     * @param array $curl_options
     */
    public function __construct($url, $timeout = 10, $redirects = 5, $headers = null, $useragent = null, $force_fsockopen = false, $curl_options = []) {
        $this->webClient = $curl_options[WebClient::class];
        $this->url = $url;
        $this->permanent_url = $url;
        $this->useragent = $useragent;
        if ($headers === null) {
            $headers = [];
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            $this->method = SimplePie::FILE_SOURCE_REMOTE | SimplePie::FILE_SOURCE_CURL;

            $client = $this->webClient->getHttpClient();
            try {
                $response = $client->get($url, [
                    'allow_redirects' => [
                        'max' => $redirects,
                        'track_redirects' => true,
                    ],
                    'headers' => [
                        'User-Agent' => $useragent,
                        'Referer' => $url,
                    ] + $headers,
                    'timeout' => $timeout,
                    'connect_timeout' => $timeout,
                ]);

                $this->headers = $response->getHeaders();

                // SimplePie expects the headers to be lower-case and strings but Guzzle returns original case and string arrays as mandated by PSR-7.
                $this->headers = array_change_key_case($this->headers, CASE_LOWER);
                array_walk($this->headers, function(&$value, $header) {
                    // There can be multiple header values if and only if the header is described as a list, in which case, they can be coalesced into a single string, separated by commas:
                    // https://tools.ietf.org/html/rfc2616#section-4.2
                    // Non-compliant servers might send multiple instances of single non-list header; we will use the last value then.
                    // For Simplicity, we consider every header other than Content-Type a list, since it is what SimplePie does.
                    if ($header === 'content-type') {
                        $value = array_pop($value);
                    } else {
                        $value = implode(', ', $value);
                    }
                });

                $this->url = WebClient::getEffectiveUrl($url, $response);
                $this->body = (string) $response->getBody();
                $this->status_code = $response->getStatusCode();
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $this->error = $e->getMessage();
                $this->success = false;
            }
        } else {
            $this->method = SimplePie::FILE_SOURCE_LOCAL | SimplePie::FILE_SOURCE_FILE_GET_CONTENTS;
            if (empty($url) || !($this->body = trim(file_get_contents($url)))) {
                $this->error = 'file_get_contents could not read the file';
                $this->success = false;
            }
        }
    }
}
