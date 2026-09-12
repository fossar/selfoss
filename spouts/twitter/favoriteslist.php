<?php

namespace spouts\twitter;

use Abraham\TwitterOAuth\TwitterOAuth;

class favoriteslist extends \spouts\twitter\usertimeline {
    public function __construct() {
        $this->name = 'Twitter - Favorites list';
        $this->description = 'The favorites list';
        $this->params = [
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
            'count' => [
                'title' => 'Number of records to retrieve',
                'type' => 'text',
                'default' => '20',
                'required' => true,
                'validation' => ['notempty']
            ],
            'owner_screen_name' => [
                'title' => 'Username',
                'type' => 'text',
                'default' => '',
                'required' => true,
                'validation' => ['notempty']
            ]
        ];
    }

    /**
     * loads content for given list
     *
     * @param mixed $params the params of this source
     *
     * @return void
     */
    public function load($params) {
        $access_token_used = !empty($params['access_token']) && !empty($params['access_token_secret']);
        $twitter = new TwitterOAuth($params['consumer_key'], $params['consumer_secret'], $access_token_used ? $params['access_token'] : null, $access_token_used ? $params['access_token_secret'] : null);
        $timeline = $twitter->get('favorites/list',
            [
                'count' => $params['count'],
                'owner_screen_name' => $params['owner_screen_name'],
                'include_entities' => 1,
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

        $this->htmlUrl = 'https://twitter.com/' . urlencode($params['owner_screen_name']);

        $this->spoutTitle = "@{$params['owner_screen_name']}/favorites";
    }
}
