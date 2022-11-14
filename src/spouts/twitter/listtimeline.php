<?php

declare(strict_types=1);

namespace spouts\twitter;

/**
 * Spout for fetching a twitter list
 *
 * @copyright  Copyright (c) Nicola Malizia (https://unnikked.ga/)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Nicola Malizia <unnikked@gmail.com>
 */
class listtimeline extends \spouts\twitter\usertimeline {
    public $name = 'Twitter: list timeline';
    public $description = 'Fetch the timeline of a given list.';
    public $params = [
        'consumer_key' => [
            'title' => 'Consumer Key',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
        'consumer_secret' => [
            'title' => 'Consumer Secret',
            'type' => 'password',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
        'access_token' => [
            'title' => 'Access Token (optional)',
            'type' => 'text',
            'default' => '',
            'required' => false,
            'validation' => [],
        ],
        'access_token_secret' => [
            'title' => 'Access Token Secret (optional)',
            'type' => 'password',
            'default' => '',
            'required' => false,
            'validation' => [],
        ],
        'slug' => [
            'title' => 'List Slug',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
        'owner_screen_name' => [
            'title' => 'Username',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
    ];

    public function load(array $params): void {
        $this->client = $this->getHttpClient($params['consumer_key'], $params['consumer_secret'], $params['access_token'] ?? null, $params['access_token_secret'] ?? null);

        $this->items = $this->fetchTwitterTimeline('lists/statuses', [
            'slug' => $params['slug'],
            'owner_screen_name' => $params['owner_screen_name'],
        ]);

        $this->htmlUrl = 'https://twitter.com/' . urlencode($params['owner_screen_name']);

        $this->title = "@{$params['owner_screen_name']}/{$params['slug']}";
    }
}
