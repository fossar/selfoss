<?php

namespace spouts\github;

use helpers\WebClient;

/**
 * Spout for fetching from GitHub
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Tim Gerundt <tim@gerundt.de>
 */
class commits extends \spouts\spout {
    /** @var string name of source */
    public $name = 'GitHub: commits';

    /** @var string description of this source type */
    public $description = 'List commits on a repository.';

    /** @var array configurable parameters */
    public $params = [
        'owner' => [
            'title' => 'Owner',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'repo' => [
            'title' => 'Repository',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'branch' => [
            'title' => 'Branch',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ]
    ];

    /** @var ?array current fetched items */
    protected $items = null;

    /** @var string global html url for the source */
    protected $htmlUrl = '';

    /** @var string URL of the favicon */
    protected $faviconUrl = 'https://assets-cdn.github.com/favicon.ico';

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
     *
     * @param array $params the params of this source
     *
     * @throws \GuzzleHttp\Exception\RequestException When an error is encountered
     *
     * @return void
     */
    public function load(array $params) {
        $this->htmlUrl = 'https://github.com/' . urlencode($params['owner']) . '/' . urlencode($params['repo']) . '/' . urlencode($params['branch']);

        $jsonUrl = 'https://api.github.com/repos/' . urlencode($params['owner']) . '/' . urlencode($params['repo']) . '/commits?sha=' . urlencode($params['branch']);

        $http = WebClient::getHttpClient();
        $response = $http->get($jsonUrl);
        $this->items = json_decode((string) $response->getBody(), true);

        $this->spoutTitle = "Recent Commits to {$params['repo']}:{$params['branch']}";
    }

    /**
     * returns the global html url for the source
     *
     * @return string url as html
     */
    public function getHtmlUrl() {
        return $this->htmlUrl;
    }

    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    public function getId() {
        if ($this->items !== null && $this->valid()) {
            return @current($this->items)['sha'];
        }

        return null;
    }

    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getTitle() {
        if ($this->items !== null && $this->valid()) {
            $message = @current($this->items)['commit']['message'];

            return htmlspecialchars(self::cutTitle($message));
        }

        return null;
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if ($this->items !== null && $this->valid()) {
            $message = @current($this->items)['commit']['message'];

            return nl2br(htmlspecialchars($message), false);
        }

        return null;
    }

    /**
     * returns the icon of this item
     *
     * @return string icon url
     */
    public function getIcon() {
        return $this->faviconUrl;
    }

    /**
     * returns the link of this item
     *
     * @return string link
     */
    public function getLink() {
        if ($this->items !== null && $this->valid()) {
            return @current($this->items)['html_url'];
        }

        return null;
    }

    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getDate() {
        if ($this->items !== null && $this->valid()) {
            $date = date('Y-m-d H:i:s', strtotime(@current($this->items)['commit']['author']['date']));
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
     * cut title after X chars (from the first line)
     *
     * @param string $title title
     * @param int $cutafter Cut after X chars
     *
     * @return string Cutted title
     */
    public static function cutTitle($title, $cutafter = 69) {
        $title = strtok($title, "\n");
        if (($cutafter > 0) && (strlen($title) > $cutafter)) {
            return substr($title, 0, $cutafter) . '…';
        }

        return $title;
    }
}
