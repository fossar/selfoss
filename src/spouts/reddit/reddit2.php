<?php

namespace spouts\reddit;

use GuzzleHttp;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use helpers\Image;
use helpers\WebClient;
use Stringy\Stringy as S;

/**
 * Spout for fetching from reddit
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class reddit2 extends \spouts\spout {
    use \helpers\ItemsIterator;

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
            'validation' => ['notempty']
        ],
        'username' => [
            'title' => 'Username',
            'type' => 'text',
            'default' => '',
            'required' => false,
            'validation' => ''
        ],
        'password' => [
            'title' => 'Password',
            'type' => 'password',
            'default' => '',
            'required' => false,
            'validation' => ''
        ]
    ];

    /** @var string the reddit_session cookie */
    private $reddit_session = '';

    /** @var string favicon url */
    private $faviconUrl = '';

    /** @var Image image helper */
    private $imageHelper;

    /** @var WebClient */
    private $webClient;

    public function __construct(Image $imageHelper, WebClient $webClient) {
        $this->imageHelper = $imageHelper;
        $this->webClient = $webClient;
    }

    public function load(array $params) {
        if (!empty($params['password']) && !empty($params['username'])) {
            if (function_exists('apc_fetch')) {
                $this->reddit_session = apc_fetch("{$params['username']}_selfoss_reddit_session");
                if (empty($this->reddit_session)) {
                    $this->login($params);
                }
            } else {
                $this->login($params);
            }
        }

        // ensure the URL is absolute
        $url = UriResolver::resolve(new Uri('https://www.reddit.com/'), new Uri($params['url']));
        // and that the path ends with .json (Reddit does not seem to recogize Accept header)
        $url = $url->withPath((string) S::create($url->getPath())->ensureRight('.json'));

        $response = $this->sendRequest($url);
        $json = json_decode((string) $response->getBody(), true);

        if (isset($json['error'])) {
            throw new \Exception($json['message']);
        }

        $this->items = $json['data']['children'];
    }

    public function getId() {
        if ($this->items !== null && $this->valid()) {
            $id = @current($this->items)['data']['id'];
            if (strlen($id) > 255) {
                $id = md5($id);
            }

            return $id;
        }

        return null;
    }

    public function getTitle() {
        if ($this->items !== null && $this->valid()) {
            return @current($this->items)['data']['title'];
        }

        return null;
    }

    public function getHtmlUrl() {
        if ($this->items !== null && $this->valid()) {
            // Reddit escapes HTML, we can get away with just ampersands, since quotes and angle brackets are excluded from URLs.
            return htmlspecialchars_decode(current($this->items)['data']['url'], ENT_NOQUOTES);
        }

        return null;
    }

    public function getContent() {
        if ($this->items !== null && $this->valid()) {
            $data = @current($this->items)['data'];
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

            if (preg_match('/\.(?:gif|jpg|png|svg)$/i', (new Uri($this->getHtmlUrl()))->getPath())) {
                return '<img src="' . $this->getHtmlUrl() . '" />';
            }

            return $data['url'];
        }

        return null;
    }

    public function getIcon() {
        $htmlUrl = $this->getHtmlUrl();
        if ($htmlUrl && ($iconData = $this->imageHelper->fetchFavicon($htmlUrl)) !== null) {
            list($this->faviconUrl, $iconBlob) = $iconData;
        }

        return $this->faviconUrl;
    }

    public function getLink() {
        if ($this->items !== null && $this->valid()) {
            return 'https://www.reddit.com' . @current($this->items)['data']['permalink'];
        }

        return null;
    }

    public function getThumbnail() {
        if ($this->items !== null && $this->valid()) {
            $thumbnail = @current($this->items)['data']['thumbnail'];

            if (!in_array($thumbnail, ['default', 'self'], true)) {
                return $thumbnail;
            }
        }

        return null;
    }

    public function getdate() {
        if ($this->items !== null && $this->valid()) {
            $date = date('Y-m-d H:i:s', @current($this->items)['data']['created_utc']);
        }

        return $date;
    }

    public function destroy() {
        unset($this->items);
        $this->items = null;
    }

    public function getXmlUrl(array $params) {
        return  'reddit://' . urlencode($params['url']);
    }

    /**
     * Sign in to reddit using the credentials in params and save a session cookie
     * for further requests.
     *
     * @param array $params source parameters
     *
     * @throws GuzzleHttp\Exception\RequestException When an error is encountered
     * @throws \RuntimeException if the response body is not in JSON format
     * @throws \Exception if the credentials are invalid
     */
    private function login(array $params) {
        $http = $this->webClient->getHttpClient();
        $response = $http->post("https://ssl.reddit.com/api/login/{$params['username']}", [
            GuzzleHttp\RequestOptions::FORM_PARAMS => [
                'api_type' => 'json',
                'user' => $params['username'],
                'passwd' => $params['password']
            ]
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
     * @param string $url
     * @param string $method
     *
     * @throws GuzzleHttp\Exception\RequestException When an error is encountered
     *
     * @return GuzzleHttp\Psr7\Response
     */
    private function sendRequest($url, $method = 'GET') {
        $http = $this->webClient->getHttpClient();

        if (isset($this->reddit_session)) {
            $request = new Request($method, $url, [
                'cookies' => ['reddit_session' => $this->reddit_session]
            ]);
        } else {
            $request = new Request($method, $url);
        }

        $response = $http->send($request);

        return $response;
    }
}
