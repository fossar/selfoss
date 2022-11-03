<?php

namespace spouts\reddit;

use GuzzleHttp;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use helpers\Image;
use helpers\WebClient;
use Psr\Http\Message\ResponseInterface;
use spouts\Item;
use Stringy\Stringy as S;

/**
 * Spout for fetching from reddit
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class reddit2 extends \spouts\spout {
    /** @var string name of spout */
    public $name = 'Reddit';

    /** @var string description of this source type */
    public $description = 'Get your fix from Reddit.';

    /** @var array configurable parameters */
    public $params = [
        'url' => [
            'title' => 'Subreddit or multireddit url',
            'type' => 'text',
            'default' => 'r/worldnews/top',
            'required' => true,
            'validation' => ['notempty'],
        ],
        'username' => [
            'title' => 'Username',
            'type' => 'text',
            'default' => '',
            'required' => false,
            'validation' => '',
        ],
        'password' => [
            'title' => 'Password',
            'type' => 'password',
            'default' => '',
            'required' => false,
            'validation' => '',
        ],
    ];

    /** @var ?string URL of the source */
    protected $htmlUrl = null;

    /** @var string the reddit_session cookie */
    private $reddit_session = '';

    /** @var Image image helper */
    private $imageHelper;

    /** @var WebClient */
    private $webClient;

    /** @var array[] current fetched items */
    private $items = [];

    public function __construct(Image $imageHelper, WebClient $webClient) {
        $this->imageHelper = $imageHelper;
        $this->webClient = $webClient;
    }

    public function load(array $params) {
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
        $url = $url->withPath((string) S::create($url->getPath())->ensureRight('.json'));

        $response = $this->sendRequest((string) $url);
        $json = json_decode((string) $response->getBody(), true);

        if (isset($json['error'])) {
            throw new \Exception($json['message']);
        }

        if (isset($json['data']) && isset($json['data']['children'])) {
            $this->items = $json['data']['children'];
        }
    }

    /**
     * @return ?string
     */
    public function getHtmlUrl() {
        return $this->htmlUrl;
    }

    /**
     * @return string
     */
    public function getXmlUrl(array $params) {
        return 'reddit://' . urlencode($params['url']);
    }

    /**
     * @return \Generator<Item<null>> list of items
     */
    public function getItems() {
        foreach ($this->items as $item) {
            // Reddit escapes HTML, we can get away with just ampersands, since quotes and angle brackets are excluded from URLs.
            $url = htmlspecialchars_decode($item['data']['url'], ENT_NOQUOTES);

            $id = $item['data']['id'];
            if (strlen($id) > 255) {
                $id = md5($id);
            }
            $title = $item['data']['title'];
            $content = $this->getContent($url, $item);
            $thumbnail = $this->getThumbnail($item);
            $icon = $this->findSiteIcon($url);
            $link = 'https://www.reddit.com' . $item['data']['permalink'];
            // UNIX timestamp
            // https://www.reddit.com/r/redditdev/comments/3qsv97/whats_the_time_unit_for_created_utc_and_what_time/
            $date = new \DateTimeImmutable('@' . $item['data']['created_utc']);
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

    /**
     * @param string $url
     *
     * @return string
     */
    private function getContent($url, array $item) {
        $data = $item['data'];
        $text = $data['selftext_html'];
        if (!empty($text)) {
            return htmlspecialchars_decode($text);
        }

        if (isset($data['preview']) && isset($data['preview']['images'])) {
            $text = '';
            foreach ($data['preview']['images'] as $image) {
                if (isset($image['source']) && isset($image['source']['url'])) {
                    $text .= '<img src="' . $image['source']['url'] . '">';
                }
            }

            if ($text !== '') {
                return $text;
            }
        }

        if (preg_match('/\.(?:gif|jpg|png|svg)$/i', (new Uri($url))->getPath())) {
            return '<img src="' . $url . '" />';
        }

        return $data['url'];
    }

    /**
     * @param string $url
     *
     * @return ?string
     */
    private function findSiteIcon($url) {
        $faviconUrl = null;
        if ($url && ($iconData = $this->imageHelper->fetchFavicon($url)) !== null) {
            [$faviconUrl, $iconBlob] = $iconData;
        }

        return $faviconUrl;
    }

    /**
     * @return ?string
     */
    private function getThumbnail(array $item) {
        $thumbnail = $item['data']['thumbnail'];

        if (!in_array($thumbnail, ['default', 'self'], true)) {
            return $thumbnail;
        }

        return null;
    }

    public function destroy() {
        unset($this->items);
        $this->items = [];
    }

    /**
     * Sign in to reddit using the credentials in params and save a session cookie
     * for further requests.
     *
     * @param array $params source parameters
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
