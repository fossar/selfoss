<?php

declare(strict_types=1);

namespace spouts\reddit;

use GuzzleHttp;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use helpers\HtmlString;
use helpers\Image;
use helpers\WebClient;
use Psr\Http\Message\ResponseInterface;
use spouts\Item;
use spouts\Parameter;

/**
 * Spout for fetching from reddit
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 *
 * @phpstan-type RedditItem array{data: array{id: string, url: string, title: string, permalink: string, selftext_html: string, created_utc: int, preview?: array{images?: array<array{source?: array{url?: string}}>}, thumbnail: string}}
 * @phpstan-type RedditParams array{url: string, username?: string, password?: string}
 *
 * @extends \spouts\spout<null>
 */
class reddit2 extends \spouts\spout {
    public string $name = 'Reddit';

    public string $description = 'Get your fix from Reddit.';

    public array $params = [
        'url' => [
            'title' => 'Subreddit or multireddit url',
            'type' => Parameter::TYPE_TEXT,
            'default' => 'r/worldnews/top',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'username' => [
            'title' => 'Username',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => false,
            'validation' => [],
        ],
        'password' => [
            'title' => 'Password',
            'type' => Parameter::TYPE_PASSWORD,
            'default' => '',
            'required' => false,
            'validation' => [],
        ],
    ];

    /** URL of the source */
    protected ?string $htmlUrl = null;

    /** the reddit_session cookie */
    private string $reddit_session = '';

    /** @var RedditItem[] current fetched items */
    private array $items = [];

    public function __construct(
        private Image $imageHelper,
        private WebClient $webClient
    ) {
    }

    /**
     * @param RedditParams $params
     */
    public function load(array $params): void {
        if (!empty($params['password']) && !empty($params['username'])) {
            if (function_exists('apc_fetch')) {
                $this->reddit_session = apc_fetch("{$params['username']}_selfoss_reddit_session") ?: '';
                if (empty($this->reddit_session)) {
                    $this->login($params);
                }
            } else {
                $this->login($params);
            }
        }

        // ensure the URL is absolute
        $url = UriResolver::resolve(new Uri('https://www.reddit.com/'), new Uri($params['url']));
        $this->htmlUrl = (string) $url;
        // and that the path ends with .json (Reddit does not seem to recogize Accept header)
        $path = $url->getPath();
        $url = $url->withPath(str_ends_with($path, '.json') ? $path : ($path . '.json'));

        $response = $this->sendRequest((string) $url);
        $json = json_decode((string) $response->getBody(), true);

        if (isset($json['error'])) {
            throw new \Exception($json['message']);
        }

        if (isset($json['data']) && isset($json['data']['children'])) {
            $this->items = $json['data']['children'];
        }
    }

    public function getHtmlUrl(): ?string {
        return $this->htmlUrl;
    }

    /**
     * @param RedditParams $params
     */
    public function getXmlUrl(array $params): string {
        return 'reddit://' . urlencode($params['url']);
    }

    /**
     * @return \Generator<Item<null>> list of items
     */
    public function getItems(): iterable {
        foreach ($this->items as $item) {
            // Reddit escapes HTML, we can get away with just ampersands, since quotes and angle brackets are excluded from URLs.
            $url = htmlspecialchars_decode($item['data']['url'], ENT_NOQUOTES);

            $id = $item['data']['id'];
            if (strlen($id) > 255) {
                $id = md5($id);
            }
            $title = HtmlString::fromPlainText($item['data']['title']);
            $content = $this->getContent($url, $item);
            $thumbnail = $this->getThumbnail($item);
            $icon = fn(Item $item): ?string => $this->findSiteIcon($url);
            $link = 'https://www.reddit.com' . $item['data']['permalink'];
            // UNIX timestamp
            // https://www.reddit.com/r/redditdev/comments/3qsv97/whats_the_time_unit_for_created_utc_and_what_time/
            $date = new \DateTimeImmutable('@' . $item['data']['created_utc']);
            $author = null;

            yield new Item(
                id: $id,
                title: $title,
                content: $content,
                thumbnail: $thumbnail,
                icon: $icon,
                link: $link,
                date: $date,
                author: $author,
                extraData: null,
            );
        }
    }

    /**
     * @param RedditItem $item
     */
    private function getContent(string $url, array $item): HtmlString {
        $data = $item['data'];
        // Contains escaped HTML or null.
        $text = $data['selftext_html'];
        if (!empty($text)) {
            return HtmlString::fromRaw(htmlspecialchars_decode($text));
        }

        if (isset($data['preview']) && isset($data['preview']['images'])) {
            $text = '';
            foreach ($data['preview']['images'] as $image) {
                if (isset($image['source']) && isset($image['source']['url'])) {
                    // url is already HTML-escaped.
                    $text .= '<img src="' . $image['source']['url'] . '">';
                }
            }

            if ($text !== '') {
                return HtmlString::fromRaw($text);
            }
        }

        if (preg_match('/\.(?:gif|jpg|png|svg)$/i', (new Uri($url))->getPath())) {
            return HtmlString::fromRaw('<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" />');
        }

        // Already HTML escaped.
        return HtmlString::fromRaw($data['url']);
    }

    private function findSiteIcon(string $url): ?string {
        $faviconUrl = null;
        if ($url && ($iconData = $this->imageHelper->fetchFavicon($url)) !== null) {
            [$faviconUrl, $iconBlob] = $iconData;
        }

        return $faviconUrl;
    }

    /**
     * @param RedditItem $item
     */
    private function getThumbnail(array $item): ?string {
        $thumbnail = $item['data']['thumbnail'];

        if (!in_array($thumbnail, ['default', 'self'], true)) {
            return $thumbnail;
        }

        return null;
    }

    public function destroy(): void {
        unset($this->items);
        $this->items = [];
    }

    /**
     * Sign in to reddit using the credentials in params and save a session cookie
     * for further requests.
     *
     * @param array<string, string> $params source parameters
     *
     * @throws GuzzleHttp\Exception\GuzzleException When an error is encountered
     * @throws \RuntimeException if the response body is not in JSON format
     * @throws \Exception if the credentials are invalid
     */
    private function login(array $params): void {
        $http = $this->webClient->getHttpClient();
        $response = $http->post("https://ssl.reddit.com/api/login/{$params['username']}", [
            GuzzleHttp\RequestOptions::FORM_PARAMS => [
                'api_type' => 'json',
                'user' => $params['username'],
                'passwd' => $params['password'],
            ],
        ]);
        $data = json_decode((string) $response->getBody(), true);
        if (count($data['json']['errors']) > 0) {
            $errors = '';
            foreach ($data['json']['errors'] as $error) {
                $errors .= $error[1] . PHP_EOL;
            }
            throw new \Exception($errors);
        } else {
            $this->reddit_session = $data['json']['data']['cookie'];
            if (function_exists('apc_store')) {
                apc_store("{$params['username']}_selfoss_reddit_session", $this->reddit_session, 3600);
            }
        }
    }

    /**
     * Send a HTTP request to given URL, possibly with a cookie.
     *
     * @throws GuzzleHttp\Exception\GuzzleException When an error is encountered
     */
    private function sendRequest(string $url, string $method = 'GET'): ResponseInterface {
        $http = $this->webClient->getHttpClient();

        if (!empty($this->reddit_session)) {
            $request = new Request($method, $url, [
                'cookies' => ['reddit_session' => $this->reddit_session],
            ]);
        } else {
            $request = new Request($method, $url);
        }

        $response = $http->send($request);

        return $response;
    }
}
