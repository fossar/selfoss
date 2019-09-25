<?php

namespace spouts\twitter;

use Abraham\TwitterOAuth\TwitterOAuth;

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

    /**
     * loads content for given twitter user
     *
     * @param array $params the params of this source
     *
     * @return void
     */
    public function load(array $params) {
        $twitter = new TwitterOAuth($params['consumer_key'], $params['consumer_secret'], $params['access_key'], $params['access_secret']);
        $timeline = $twitter->get('statuses/home_timeline', [
            'include_rts' => 1,
            'count' => 50,
            'tweet_mode' => 'extended',
        ]);

        if (isset($timeline->errors)) {
            $errors = '';

            foreach ($timeline->errors as $error) {
                $errors .= $error->message . "\n";
            }

            throw new \Exception($errors);
        }

        if (!is_array($timeline)) {
            throw new \Exception('invalid twitter response');
        }
        $this->items = $timeline;

        $this->htmlUrl = 'https://twitter.com/';

        $this->spoutTitle = 'Home timeline';
    }
}
