<?php

namespace spouts\facebook;

use GuzzleHttp\Psr7\Uri;
use helpers\WebClient;
use spouts\Item;

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
            'validation' => ['notempty'],
        ],
        'app_id' => [
            'title' => 'App ID',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
        'app_secret' => [
            'title' => 'App Secret',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
    ];

    /** @var ?string title of the source */
    protected $title = null;

    /** @var ?string page picture */
    private $pageLink;

    /** @var ?string page picture */
    private $pagePicture;

    /** @var WebClient */
    private $webClient;

    /** @var array[] current fetched items */
    private $items = [];

    public function __construct(WebClient $webClient) {
        $this->webClient = $webClient;
    }

    public function load(array $params): void {
        // https://developers.facebook.com/docs/graph-api/reference/user
        $http = $this->webClient->getHttpClient();
        $url = new Uri('https://graph.facebook.com/' . urlencode($params['user']));
        $url = $url->withQueryValues($url, [
            'access_token' => $params['app_id'] . '|' . $params['app_secret'],
            'fields' => 'name,picture{url},link,feed{id,message,created_time,attachments,permalink_url}',
        ]);
        $data = json_decode((string) $http->get($url)->getBody(), true);

        $this->title = $data['name'];
        $this->pagePicture = $data['picture']['data']['url'];
        $this->pageLink = $data['picture']['link'];
        // https://developers.facebook.com/docs/graph-api/reference/user/feed/
        $this->items = $data['feed']['data'];
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function getHtmlUrl(): ?string {
        return $this->pageLink;
    }

    public function getIcon(): ?string {
        return $this->pagePicture;
    }

    /**
     * @return \Generator<Item<null>> list of items
     */
    public function getItems(): iterable {
        foreach ($this->items as $item) {
            $id = $item['id'];
            $title = mb_strlen($item['message']) > 80 ? mb_substr($item['message'], 0, 100) . '…' : $item['message'];
            $content = $this->getPostContent($item);
            $thumbnail = null;
            $icon = null;
            $link = $item['permalink_url'];
            // The docs say UNIX timestamp but appears to be ISO 8601.
            // https://developers.facebook.com/docs/graph-api/reference/post/
            // https://stackoverflow.com/questions/14516792/what-is-the-time-format-used-in-facebook-created-date
            $date = new \DateTimeImmutable($item['created_time']);
            $author = null;

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

    private function getPostContent(array $item): string {
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
}
