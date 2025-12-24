<?php

// SPDX-FileCopyrightText: 2011–2016 Tobias Zeising <tobias.zeising@aditu.de>
// SPDX-FileCopyrightText: 2013 Tim Gerundt <tim@gerundt.de>
// SPDX-FileCopyrightText: 2014 Mario Starke <sta.ma@web.de>
// SPDX-FileCopyrightText: 2016–2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-FileCopyrightText: 2018 Binnette <binnette@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace spouts\twitter;

use spouts\Item;
use spouts\Parameter;

/**
 * Spout for fetching an rss feed
 *
 * @extends \spouts\spout<null>
 */
class usertimeline extends \spouts\spout {
    public string $name = 'Twitter: user timeline';

    public string $description = 'Fetch the timeline of a given user.';

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
        'username' => [
            'title' => 'Username',
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
     * @param array{consumer_key: string, consumer_secret: string, access_token?: string, access_token_secret?: string, username: string} $params
     */
    public function load(array $params): void {
        $client = $this->clientFactory->create(
            $params['consumer_key'],
            $params['consumer_secret'],
            $params['access_token'] ?? null,
            $params['access_token_secret'] ?? null
        );

        $this->items = $client->fetchTimeline('statuses/user_timeline', [
            'screen_name' => $params['username'],
        ]);

        $this->htmlUrl = 'https://twitter.com/' . urlencode($params['username']);

        $this->title = "@{$params['username']}";
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
