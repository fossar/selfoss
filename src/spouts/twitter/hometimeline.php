<?php

namespace spouts\twitter;

/**
 * Spout for fetching the twitter timeline of your twitter account
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class hometimeline extends \spouts\twitter\usertimeline {
    /** @var string name of source */
    public $name = 'Twitter: your timeline';

    /** @var string description of this source type */
    public $description = 'Fetch your twitter timeline.';

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
        'access_key' => [
            'title' => 'Access Key',
            'type' => 'password',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'access_secret' => [
            'title' => 'Access Secret',
            'type' => 'password',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ]
    ];

    public function load(array $params) {
        $this->client = self::getHttpClient($params['consumer_key'], $params['consumer_secret'], $params['access_key'], $params['access_secret']);

        $this->items = $this->fetchTwitterTimeline('statuses/home_timeline');

        $this->htmlUrl = 'https://twitter.com/';

        $this->spoutTitle = 'Home timeline';
    }
}
