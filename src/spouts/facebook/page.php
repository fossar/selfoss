<?php

declare(strict_types=1);

namespace spouts\facebook;

use GuzzleHttp\Psr7\Uri;
use helpers\HtmlString;
use helpers\WebClient;
use spouts\Item;
use spouts\Parameter;

/**
 * Spout for fetching a facebook page feed
 *
 * https://developers.facebook.com/docs/graph-api/reference/v2.9/page/feed
 *
 * @copyright Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author Tobias Zeising <tobias.zeising@aditu.de>
 * @author Jan Tojnar <jtojnar@gmail.com>
 *
 * @phpstan-type FbAttachment array{type: string, target: array{url: string}, media: array{image: array{src: string}}, description: string}
 * @phpstan-type FbItem array{id: string, message: string, permalink_url: string, created_time: string, attachments?: array{data: array<FbAttachment>}}
 * @phpstan-type FbParams array{user: string, app_id: string, app_secret: string}
 *
 * @extends \spouts\spout<null>
 */
class page extends \spouts\spout {
    public string $name = 'Facebook: page feed';

    public string $description = 'Get posts from given Facebook page wall.';

    public array $params = [
        'user' => [
            'title' => 'Page name',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'app_id' => [
            'title' => 'App ID',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'app_secret' => [
            'title' => 'App Secret',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
    ];

    /** Title of the source */
    protected ?string $title = null;

    private ?string $pageLink = null;

    private ?string $pagePicture = null;

    /** @var FbItem[] current fetched items */
    private array $items = [];

    private WebClient $webClient;

    public function __construct(WebClient $webClient) {
        $this->webClient = $webClient;
    }

    /**
     * @param FbParams $params
     */
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
            $title = HtmlString::fromPlainText(mb_strlen($item['message']) > 80 ? mb_substr($item['message'], 0, 100) . '…' : $item['message']);
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
                $author,
                null
            );
        }
    }

    /**
     * @param FbItem $item
     */
    private function getPostContent(array $item): HtmlString {
        $message = htmlspecialchars($item['message']);

        if (isset($item['attachments']) && count($item['attachments']['data']) > 0) {
            foreach ($item['attachments']['data'] as $media) {
                if ($media['type'] === 'photo') {
                    $message .= '<figure>' . PHP_EOL;
                    $message .= '<a href="' . htmlspecialchars($media['target']['url'], ENT_QUOTES) . '"><img src="' . htmlspecialchars($media['media']['image']['src'], ENT_QUOTES) . '" alt=""></a>' . PHP_EOL;

                    // Some photos will have the same description, no need to display it twice
                    if ($media['description'] !== $item['message']) {
                        $message .= '<figcaption>' . htmlspecialchars($media['description']) . '</figcaption>' . PHP_EOL;
                    }

                    $message .= '</figure>' . PHP_EOL;
                }
            }
        }

        return HtmlString::fromRaw($message);
    }
}
