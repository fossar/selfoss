<?php

// SPDX-FileCopyrightText: 2011 Tobias Zeising <tobias.zeising@aditu.de>
// SPDX-FileCopyrightText: 2020–2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace spouts\twitter;

use spouts\Item;
use spouts\Parameter;

/**
 * Spout for fetching a Twitter search
 *
 * @extends \spouts\spout<null>
 */
class Search extends \spouts\spout {
    public string $name = 'Twitter: search';

    public string $description = 'Fetch the search results for given query.';

    public array $params = [
        'consumer_key' => [
            'title' => 'Consumer Key',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'consumer_secret' => [
            'title' => 'Consumer Secret',
            'type' => Parameter::TYPE_PASSWORD,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'access_token' => [
            'title' => 'Access Token (optional)',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => false,
            'validation' => [],
        ],
        'access_token_secret' => [
            'title' => 'Access Token Secret (optional)',
            'type' => Parameter::TYPE_PASSWORD,
            'default' => '',
            'required' => false,
            'validation' => [],
        ],
        'query' => [
            'title' => 'Search query',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
    ];

    /** URL of the source */
    private string $htmlUrl = '';

    /** Title of the source */
    private ?string $title = null;

    /** @var iterable<Item<null>> current fetched items */
    private iterable $items = [];

    public function __construct(
        private readonly TwitterV1ApiClientFactory $clientFactory
    ) {
    }

    /**
     * @param array{consumer_key: string, consumer_secret: string, access_token?: string, access_token_secret?: string, query: string} $params
     */
    public function load(array $params): void {
        $client = $this->clientFactory->create(
            $params['consumer_key'],
            $params['consumer_secret'],
            $params['access_token'] ?? null,
            $params['access_token_secret'] ?? null
        );

        $this->items = $client->fetchTimeline('search/tweets', [
            'q' => $params['query'],
            'result_type' => 'recent',
        ]);

        $this->htmlUrl = 'https://twitter.com/search?q=' . urlencode($params['query']);

        $this->title = "Search twitter for {$params['query']}";
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function getHtmlUrl(): ?string {
        return $this->htmlUrl;
    }

    /**
     * @return iterable<Item<null>> list of items
     */
    public function getItems(): iterable {
        return $this->items;
    }

    public function destroy(): void {
        unset($this->items);
        $this->items = [];
    }
}
