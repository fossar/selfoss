<?php

// SPDX-FileCopyrightText: 2019–2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace spouts\twitter;

use GuzzleHttp;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use helpers\WebClient;

/**
 * Factory for TwitterV1ApiClient.
 */
class TwitterV1ApiClientFactory {
    private WebClient $webClient;

    public function __construct(WebClient $webClient) {
        $this->webClient = $webClient;
    }

    /**
     * Provide a HTTP client for use by spouts
     */
    public function create(
        string $consumerKey,
        string $consumerSecret,
        ?string $accessToken,
        ?string $accessTokenSecret
    ): TwitterV1ApiClient {
        $access_token_used = !empty($accessToken) && !empty($accessTokenSecret);

        $config = $this->webClient->createHttpClientConfig();

        $config['base_uri'] = 'https://api.twitter.com/1.1/';
        $config['auth'] = 'oauth';
        $middleware = new Oauth1([
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
            'token' => $access_token_used ? $accessToken : '',
            'token_secret' => $access_token_used ? $accessTokenSecret : '',
        ]);
        $config['handler']->push($middleware);

        $httpClient = new GuzzleHttp\Client($config);

        return new TwitterV1ApiClient($httpClient);
    }
}
