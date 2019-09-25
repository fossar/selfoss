<?php

namespace spouts\facebook;

use GuzzleHttp\Psr7\Uri;
use helpers\WebClient;

/**
 * Spout for fetching a facebook page feed
 *
 * https://developers.facebook.com/docs/graph-api/reference/v2.9/page/feed
 *
 * @copyright Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author Tobias Zeising <tobias.zeising@aditu.de>
 * @author Jan Tojnar <jtojnar@gmail.com>
 */
class page extends \spouts\spout {
    /** @var string name of source */
    public $name = 'Facebook: page feed';

    /** @var string description of this source type */
    public $description = 'Get posts from given Facebook page wall.';

    /** @var array configurable parameters */
    public $params = [
        'user' => [
            'title' => 'Page name',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'app_id' => [
            'title' => 'App ID',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'app_secret' => [
            'title' => 'App Secret',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
    ];

    /** @var ?string page picture */
    private $pageLink;

    /** @var ?string page picture */
    private $pagePicture;

    /**
     * loads content for given source
     *
     * @param array $params the params of this source
     *
     * @return void
     */
    public function load(array $params) {
        $http = WebClient::getHttpClient();
        $url = new Uri('https://graph.facebook.com/' . urlencode($params['user']));
        $url = $url->withQueryValues($url, [
            'access_token' => $params['app_id'] . '|' . $params['app_secret'],
            'fields' => 'name,picture{url},link,feed{id,message,created_time,attachments,permalink_url}',
        ]);
        $data = json_decode((string) $http->get($url)->getBody(), true);

        $this->spoutTitle = $data['name'];
        $this->pagePicture = $data['picture']['data']['url'];
        $this->pageLink = $data['picture']['link'];
        $this->items = $data['feed']['data'];
    }

    public function getHtmlUrl() {
        return $this->pageLink;
    }

    public function getId() {
        if ($this->items !== false) {
            $item = current($this->items);

            return $item['id'];
        }

        return false;
    }

    public function getTitle() {
        if ($this->items !== false) {
            $item = current($this->items);

            if (mb_strlen($item['message']) > 80) {
                return mb_substr($item['message'], 0, 100) . '…';
            } else {
                return $item['message'];
            }
        }

        return false;
    }

    public function getContent() {
        if ($this->items !== false) {
            $item = current($this->items);
            $message = $item['message'];

            if (isset($item['attachments']) && count($item['attachments']['data']) > 0) {
                foreach ($item['attachments']['data'] as $media) {
                    if ($media['type'] === 'photo') {
                        $message .= '<figure>' . PHP_EOL;
                        $message .= '<a href="' . $media['target']['url'] . '"><img src="' . $media['media']['image']['src'] . '" alt=""></a>' . PHP_EOL;

                        // Some photos will have the same description, no need to display it twice
                        if ($media['description'] !== $item['message']) {
                            $message .= '<figcaption>' . $media['description'] . '</figcaption>' . PHP_EOL;
                        }

                        $message .= '</figure>' . PHP_EOL;
                    }
                }
            }

            return $message;
        }

        return false;
    }

    public function getIcon() {
        return $this->pagePicture;
    }

    public function getLink() {
        if ($this->items !== false) {
            $item = current($this->items);

            return $item['permalink_url'];
        }

        return false;
    }

    public function getDate() {
        if ($this->items !== false) {
            $item = current($this->items);

            return $item['created_time'];
        }

        return false;
    }

    //
    // Iterator Interface
    //

    /**
     * reset iterator
     *
     * @return void
     */
    public function rewind() {
        if ($this->items !== false) {
            reset($this->items);
        }
    }

    /**
     * receive current item
     *
     * @return page current item
     */
    public function current() {
        if ($this->items !== false) {
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
        if ($this->items !== false) {
            return key($this->items);
        }

        return false;
    }

    /**
     * select next item
     *
     * @return page next item
     */
    public function next() {
        if ($this->items !== false) {
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
        if ($this->items !== false) {
            return current($this->items) !== false;
        }

        return false;
    }
}
