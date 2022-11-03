<?php

namespace spouts\twitter;

use GuzzleHttp;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use helpers\WebClient;
use spouts\Item;
use stdClass;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class usertimeline extends \spouts\spout {
    /** @var string name of source */
    public $name = 'Twitter: user timeline';

    /** @var string description of this source type */
    public $description = 'Fetch the timeline of a given user.';

    /** @var array configurable parameters */
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
        'username' => [
            'title' => 'Username',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
    ];

    /** @var string URL of the source */
    protected $htmlUrl = '';

    /** @var ?GuzzleHttp\Client HTTP client configured with Twitter OAuth support */
    protected $client = null;

    /** @var WebClient */
    private $webClient;

    /** @var ?string title of the source */
    protected $title = null;

    /** @var stdClass[] current fetched items */
    protected $items = [];

    public function __construct(WebClient $webClient) {
        $this->webClient = $webClient;
    }

    /**
     * Provide a HTTP client for use by spouts
     *
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param ?string $accessToken
     * @param ?string $accessTokenSecret
     *
     * @return GuzzleHttp\Client
     */
    public function getHttpClient($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret) {
        $access_token_used = !empty($accessToken) && !empty($accessTokenSecret);

        $oldClient = $this->webClient->getHttpClient();
        $config = $oldClient->getConfig();

        $config['base_uri'] = 'https://api.twitter.com/1.1/';
        $config['auth'] = 'oauth';
        $middleware = new Oauth1([
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
            'token' => $access_token_used ? $accessToken : '',
            'token_secret' => $access_token_used ? $accessTokenSecret : '',
        ]);
        $config['handler'] = clone $config['handler']; // we do not want to contaminate other spouts
        $config['handler']->push($middleware);

        return new GuzzleHttp\Client($config);
    }

    /**
     * Fetch timeline from Twitter API.
     *
     * Assumes client property is initialized to Guzzle client configured to access Twitter.
     *
     * @param string $endpoint API endpoint to use
     * @param array $params extra query arguments to pass to the API call
     *
     * @throws \Exception when API request fails
     * @throws GuzzleHttp\Exception\GuzzleException when HTTP request fails for API-unrelated reasons
     *
     * @return stdClass[]
     */
    protected function fetchTwitterTimeline($endpoint, array $params = []) {
        if (!isset($this->client)) {
            throw new \Exception('Twitter client was not initialized.');
        }

        try {
            $response = $this->client->get("$endpoint.json", [
                'query' => array_merge([
                    'include_rts' => 1,
                    'count' => 50,
                    'tweet_mode' => 'extended',
                ], $params),
            ]);

            $timeline = json_decode((string) $response->getBody());

            if (isset($timeline->statuses)) {
                $timeline = $timeline->statuses;
            }

            if (!is_array($timeline)) {
                throw new \Exception('Invalid twitter response');
            }

            return $timeline;
        } catch (BadResponseException $e) {
            if ($e->hasResponse()) {
                $body = json_decode((string) $e->getResponse()->getBody());

                if (isset($body->errors)) {
                    $errors = implode("\n", array_map(function($error) {
                        return $error->message;
                    }, $body->errors));

                    throw new \Exception($errors, $e->getCode(), $e);
                }
            }

            throw $e;
        }
    }

    //
    // Source Methods
    //

    public function load(array $params) {
        $this->client = $this->getHttpClient($params['consumer_key'], $params['consumer_secret'], isset($params['access_token']) ? $params['access_token'] : null, isset($params['access_token_secret']) ? $params['access_token_secret'] : null);

        $this->items = $this->fetchTwitterTimeline('statuses/user_timeline', [
            'screen_name' => $params['username'],
        ]);

        $this->htmlUrl = 'https://twitter.com/' . urlencode($params['username']);

        $this->title = "@{$params['username']}";
    }

    public function getTitle() {
        return $this->title;
    }

    public function getHtmlUrl() {
        return $this->htmlUrl;
    }

    /**
     * @return \Generator<Item<null>> list of items
     */
    public function getItems() {
        foreach ($this->items as $item) {
            $author = $item->user->name;
            $targetItem = $item;
            if (isset($item->retweeted_status)) {
                $targetItem = $item->retweeted_status;
                $author .= ' (RT ' . $targetItem->user->name . ')';
            }

            $id = $item->id_str;
            $title = $this->getTweetTitle($targetItem);
            $content = $this->getContent($targetItem);
            $thumbnail = $this->getThumbnail($targetItem);
            $icon = $this->getTweetIcon($targetItem);
            $link = 'https://twitter.com/' . $item->user->screen_name . '/status/' . $item->id_str;
            // Format of `created_at` field not specified, looks US-centric.
            // https://developer.twitter.com/en/docs/twitter-api/v1/data-dictionary/object-model/tweet
            $date = new \DateTimeImmutable($item->created_at);

            yield new Item(
                $id,
                $title,
                $content,
                $thumbnail,
                $icon,
                $link,
                $date,
                $author
            );
        }
    }

    /**
     * @return string
     */
    private function getTweetTitle(stdClass $item) {
        $entities = self::formatEntities($item->entities);
        $tweet = self::replaceEntities($item->full_text, $entities);

        return $tweet;
    }

    /**
     * @return string
     */
    private function getContent(stdClass $item) {
        $result = '';

        if (isset($item->extended_entities) && isset($item->extended_entities->media) && count($item->extended_entities->media) > 0) {
            foreach ($item->extended_entities->media as $media) {
                if ($media->type === 'photo') {
                    $result .= '<p><a href="' . $media->media_url_https . ':large"><img src="' . $media->media_url_https . ':small" alt=""></a></p>' . PHP_EOL;
                }
            }
        }

        if (isset($item->quoted_status)) {
            $quoted = $item->quoted_status;
            $entities = self::formatEntities($quoted->entities);

            $result .= '<a href="https://twitter.com/' . $quoted->user->screen_name . '">@' . $quoted->user->screen_name . '</a>:';
            $result .= '<blockquote>' . self::replaceEntities($quoted->full_text, $entities) . '</blockquote>';
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getTweetIcon(stdClass $item) {
        return $item->user->profile_image_url_https;
    }

    /**
     * @return ?string
     */
    private function getThumbnail(stdClass $item) {
        if (isset($item->entities->media) && $item->entities->media[0]->type === 'photo') {
            return $item->entities->media[0]->media_url_https;
        }

        return null;
    }

    public function destroy() {
        unset($this->items);
        $this->items = [];
    }

    /**
     * convert URLs, handles and hashtags as links
     *
     * @param string $text unformated text
     * @param array $entities ordered entities
     *
     * @return string formated text
     */
    public static function replaceEntities($text, array $entities) {
        /** @var string built text */
        $result = '';
        /** @var int number of bytes in text */
        $length = strlen($text);
        /** @var int index of a byte in the text */
        $i = 0;
        /** @var int index of a UTF-8 codepoint in the text */
        $cpi = -1;
        /** @var int index of a UTF-8 codepoint where the last entity ends */
        $skipUntilCp = -1;

        while ($i < $length) {
            $c = $text[$i];

            ++$i;

            // UTF-8 continuation bytes are not counted
            if (!((ord($c) & 0b10000000) && !(ord($c) & 0b01000000))) {
                ++$cpi;
            }

            if ($skipUntilCp <= $cpi) {
                if (isset($entities[$cpi])) {
                    $entity = $entities[$cpi];
                    $appended = '<a href="' . $entity['url'] . '" target="_blank" rel="noreferrer">' . $entity['text'] . '</a>';
                    $skipUntilCp = $entity['end'];
                } else {
                    $appended = $c;
                }

                $result .= $appended;
            }
        }

        return $result;
    }

    /**
     * Convert entities returned by Twitter API into more convenient representation
     *
     * @param stdClass $groupedEntities entities returned by Twitter API
     *
     * @return array{text: string, url: string, end: int}[] flattened and ordered array of entities
     */
    public static function formatEntities(stdClass $groupedEntities) {
        $result = [];

        foreach ($groupedEntities as $type => $entities) {
            foreach ($entities as $entity) {
                $start = $entity->indices[0];
                $end = $entity->indices[1];
                if ($type === 'hashtags') {
                    $result[$start] = [
                        'text' => '#' . $entity->text,
                        'url' => 'https://twitter.com/hashtag/' . urlencode($entity->text),
                        'end' => $end,
                    ];
                } elseif ($type === 'symbols') {
                    $result[$start] = [
                        'text' => '$' . $entity->text,
                        'url' => 'https://twitter.com/search?q=%24' . urlencode($entity->text),
                        'end' => $end,
                    ];
                } elseif ($type === 'user_mentions') {
                    $result[$start] = [
                        'text' => '@' . $entity->screen_name,
                        'url' => 'https://twitter.com/' . urlencode($entity->screen_name),
                        'end' => $end,
                    ];
                } elseif ($type === 'urls' || $type === 'media') {
                    $result[$start] = [
                        'text' => $entity->display_url,
                        'url' => $entity->expanded_url,
                        'end' => $end,
                    ];
                }
            }
        }

        return $result;
    }
}
