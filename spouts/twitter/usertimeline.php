<?php

namespace spouts\twitter;

use Abraham\TwitterOAuth\TwitterOAuth;
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
        'username' => [
            'title' => 'Username',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ]
    ];

    /** @var ?array current fetched items */
    protected $items = null;

    /** @var string URL of the source */
    protected $htmlUrl = '';

    //
    // Iterator Interface
    //

    /**
     * reset iterator
     *
     * @return void
     */
    public function rewind() {
        if ($this->items !== null) {
            reset($this->items);
        }
    }

    /**
     * receive current item
     *
     * @return \SimplePie_Item current item
     */
    public function current() {
        if ($this->items !== null) {
            return $this;
        }

        return false;
    }

    /**
     * receive key of current item
     *
     * @return mixed key of current item
     */
    public function key() {
        if ($this->items !== null) {
            return key($this->items);
        }

        return false;
    }

    /**
     * select next item
     *
     * @return \SimplePie_Item next item
     */
    public function next() {
        if ($this->items !== null) {
            next($this->items);
        }

        return $this;
    }

    /**
     * end reached
     *
     * @return bool false if end reached
     */
    public function valid() {
        if ($this->items !== null) {
            return current($this->items) !== false;
        }

        return false;
    }

    //
    // Source Methods
    //

    /**
     * loads content for given source
     * I supress all Warnings of SimplePie for ensuring
     * working plugin in PHP Strict mode
     *
     * @param array $params the params of this source
     *
     * @return void
     */
    public function load(array $params) {
        $access_token_used = !empty($params['access_token']) && !empty($params['access_token_secret']);
        $twitter = new TwitterOAuth($params['consumer_key'], $params['consumer_secret'], $access_token_used ? $params['access_token'] : null, $access_token_used ? $params['access_token_secret'] : null);
        $timeline = $twitter->get('statuses/user_timeline', [
            'screen_name' => $params['username'],
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

        $this->htmlUrl = 'https://twitter.com/' . urlencode($params['username']);

        $this->spoutTitle = "@{$params['username']}";
    }

    /**
     * returns the global html url for the source
     *
     * @return string url as html
     */
    public function getHtmlUrl() {
        if (isset($this->htmlUrl)) {
            return $this->htmlUrl;
        }

        return null;
    }

    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    public function getId() {
        if ($this->items !== null) {
            return @current($this->items)->id_str;
        }

        return null;
    }

    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getTitle() {
        if ($this->items !== null) {
            $item = @current($this->items);
            $rt = '';
            if (isset($item->retweeted_status)) {
                $rt = ' (RT ' . $item->user->name . ')';
                $item = $item->retweeted_status;
            }

            $entities = self::formatEntities($item->entities);
            $tweet = $item->user->name . $rt . ':<br>' . self::replaceEntities($item->full_text, $entities);

            return $tweet;
        }

        return null;
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        $result = '';

        if ($this->items !== false) {
            $item = current($this->items);
            if (isset($item->retweeted_status)) {
                $item = $item->retweeted_status;
            }

            if (isset($item->extended_entities) && isset($item->extended_entities->media) && count($item->extended_entities->media) > 0) {
                foreach ($item->extended_entities->media as $media) {
                    if ($media->type === 'photo') {
                        $result .= '<p><a href="' . $media->media_url_https . ':large"><img src="' . $media->media_url_https . ':small" alt=""></a></p>' . PHP_EOL;
                    }
                }
            }

            if (isset($item->quoted_status)) {
                $quoted = $item->quoted_status;
                $tweet_url = 'https://twitter.com/' . $quoted->user->screen_name . '/status/' . $quoted->status_id_str;
                $entities = self::formatEntities($quoted->entities);

                $result .= '<a href="https://twitter.com/' . $quoted->user->screen_name . '">@' . $quoted->user->screen_name . '</a>:';
                $result .= '<blockquote>' . self::replaceEntities($quoted->full_text, $entities) . '</blockquote>';
            }
        }

        return $result;
    }

    /**
     * returns the icon of this item
     *
     * @return string icon url
     */
    public function getIcon() {
        if ($this->items !== null) {
            $item = @current($this->items);
            if (isset($item->retweeted_status)) {
                $item = $item->retweeted_status;
            }

            return $item->user->profile_image_url_https;
        }

        return null;
    }

    /**
     * returns the link of this item
     *
     * @return string link
     */
    public function getLink() {
        if ($this->items !== null) {
            $item = @current($this->items);

            return 'https://twitter.com/' . $item->user->screen_name . '/status/' . $item->id_str;
        }

        return null;
    }

    /**
     * returns the thumbnail of this item (for multimedia feeds)
     *
     * @return mixed thumbnail data
     */
    public function getThumbnail() {
        if ($this->items !== null) {
            $item = current($this->items);
            if (isset($item->retweeted_status)) {
                $item = $item->retweeted_status;
            }
            if (isset($item->entities->media) && $item->entities->media[0]->type === 'photo') {
                return $item->entities->media[0]->media_url_https;
            }
        }

        return '';
    }

    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getDate() {
        if ($this->items !== null) {
            $date = date('Y-m-d H:i:s', strtotime(@current($this->items)->created_at));
        }
        if (strlen($date) === 0) {
            $date = date('Y-m-d H:i:s');
        }

        return $date;
    }

    /**
     * destroy the plugin (prevent memory issues)
     */
    public function destroy() {
        unset($this->items);
        $this->items = null;
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
                    $appended = '<a href="' . $entity['url'] . '" target="_blank" rel="noopener noreferrer">' . $entity['text'] . '</a>';
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
     * @return array flattened and ordered array of entities
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
