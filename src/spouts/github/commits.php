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
    use \helpers\ItemsIterator;

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
            'validation' => ['notempty'],
        ],
        'repo' => [
            'title' => 'Repository',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
        'branch' => [
            'title' => 'Branch',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
    ];

    /** @var string global html url for the source */
    protected $htmlUrl = '';

    /** @var string URL of the favicon */
    protected $faviconUrl = 'https://assets-cdn.github.com/favicon.ico';

    /** @var WebClient */
    private $webClient;

    public function __construct(WebClient $webClient) {
        $this->webClient = $webClient;
    }

    //
    // Source Methods
    //

    public function load(array $params) {
        $this->htmlUrl = 'https://github.com/' . urlencode($params['owner']) . '/' . urlencode($params['repo']) . '/' . urlencode($params['branch']);

        $jsonUrl = 'https://api.github.com/repos/' . urlencode($params['owner']) . '/' . urlencode($params['repo']) . '/commits?sha=' . urlencode($params['branch']);

        $http = $this->webClient->getHttpClient();
        $response = $http->get($jsonUrl);
        $this->items = json_decode((string) $response->getBody(), true);

        $this->spoutTitle = "Recent Commits to {$params['repo']}:{$params['branch']}";
    }

    public function getHtmlUrl() {
        return $this->htmlUrl;
    }

    public function getId() {
        if ($this->items !== null && $this->valid()) {
            return @current($this->items)['sha'];
        }

        return null;
    }

    public function getTitle() {
        if ($this->items !== null && $this->valid()) {
            $message = @current($this->items)['commit']['message'];

            return htmlspecialchars(self::cutTitle($message));
        }

        return null;
    }

    public function getContent() {
        if ($this->items !== null && $this->valid()) {
            $message = @current($this->items)['commit']['message'];

            return nl2br(htmlspecialchars($message), false);
        }

        return null;
    }

    public function getIcon() {
        return $this->faviconUrl;
    }

    public function getLink() {
        if ($this->items !== null && $this->valid()) {
            return @current($this->items)['html_url'];
        }

        return null;
    }

    public function getDate() {
        if ($this->items !== null && $this->valid()) {
            $date = date('Y-m-d H:i:s', strtotime(@current($this->items)['commit']['author']['date']));
        }
        if (strlen($date) === 0) {
            $date = date('Y-m-d H:i:s');
        }

        return $date;
    }

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
