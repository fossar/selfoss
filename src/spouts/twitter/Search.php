<?php

declare(strict_types=1);

namespace spouts\twitter;

use spouts\Parameter;

/**
 * Spout for fetching a Twitter search
 *
 * @author Jan Tojnar <jtojnar@gmail.com>
 * @copyright Jan Tojnar <jtojnar@gmail.com>
 * @license GPL-3.0-or-later
 */
class Search extends \spouts\twitter\usertimeline {
    /** @var string name of source */
    public $name = 'Twitter: search';

    /** @var string description of this source type */
    public $description = 'Fetch the search results for given query.';

    /** @var SpoutParameters configurable parameters */
    public $params = [
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

    public function load(array $params): void {
        $this->client = $this->getHttpClient($params['consumer_key'], $params['consumer_secret'], $params['access_token'] ?? null, $params['access_token_secret'] ?? null);

        $this->items = $this->fetchTwitterTimeline('search/tweets', [
            'q' => $params['query'],
            'result_type' => 'recent',
        ]);

        $this->htmlUrl = 'https://twitter.com/search?q=' . urlencode($params['query']);

        $this->title = "Search twitter for {$params['query']}";
    }
}
