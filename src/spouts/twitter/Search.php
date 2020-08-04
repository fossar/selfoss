<?php

namespace spouts\twitter;

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

    /** @var array configurable parameters */
    public $params = [
        'consumer_key' => [
            'title' => 'Consumer Key',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'consumer_secret' => [
            'title' => 'Consumer Secret',
            'type' => 'password',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'access_token' => [
            'title' => 'Access Token (optional)',
            'type' => 'text',
            'default' => '',
            'required' => false,
            'validation' => []
        ],
        'access_token_secret' => [
            'title' => 'Access Token Secret (optional)',
            'type' => 'password',
            'default' => '',
            'required' => false,
            'validation' => []
        ],
        'query' => [
            'title' => 'Search query',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
    ];

    public function load(array $params) {
        $this->client = $this->getHttpClient($params['consumer_key'], $params['consumer_secret'], $params['access_token'], $params['access_token_secret']);

        $this->items = $this->fetchTwitterTimeline('search/tweets', [
            'q' => $params['query'],
            'result_type' => 'recent',
        ]);

        $this->htmlUrl = 'https://twitter.com/search?q=' . urlencode($params['query']);

        $this->spoutTitle = "Search twitter for {$params['query']}";
    }
}
