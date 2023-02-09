<?php

declare(strict_types=1);

namespace spouts\github;

use helpers\WebClient;
use spouts\Item;
use spouts\Parameter;

/**
 * Spout for fetching from GitHub
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Tim Gerundt <tim@gerundt.de>
 *
 * @extends \spouts\spout<null>
 */
class commits extends \spouts\spout {
    /** @var string name of source */
    public $name = 'GitHub: commits';

    /** @var string description of this source type */
    public $description = 'List commits on a repository.';

    /** @var SpoutParameters configurable parameters */
    public $params = [
        'owner' => [
            'title' => 'Owner',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'repo' => [
            'title' => 'Repository',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'branch' => [
            'title' => 'Branch',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
    ];

    /** @var ?string title of the source */
    protected $title = null;

    /** @var string global html url for the source */
    protected $htmlUrl = '';

    /** @var string URL of the favicon */
    protected $faviconUrl = 'https://assets-cdn.github.com/favicon.ico';

    /** @var WebClient */
    private $webClient;

    /** @var array[] current fetched items */
    private $items = [];

    public function __construct(WebClient $webClient) {
        $this->webClient = $webClient;
    }

    //
    // Source Methods
    //

    public function load(array $params): void {
        $this->htmlUrl = 'https://github.com/' . urlencode($params['owner']) . '/' . urlencode($params['repo']) . '/' . urlencode($params['branch']);

        // https://docs.github.com/en/rest/commits/commits#list-commits
        $jsonUrl = 'https://api.github.com/repos/' . urlencode($params['owner']) . '/' . urlencode($params['repo']) . '/commits?sha=' . urlencode($params['branch']);

        $http = $this->webClient->getHttpClient();
        $response = $http->get($jsonUrl);
        $items = json_decode((string) $response->getBody(), true);
        $this->items = $items ?? [];

        $this->title = "Recent Commits to {$params['repo']}:{$params['branch']}";
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function getHtmlUrl(): ?string {
        return $this->htmlUrl;
    }

    public function getIcon(): ?string {
        return $this->faviconUrl;
    }

    /**
     * @return \Generator<Item<null>> list of items
     */
    public function getItems(): iterable {
        foreach ($this->items as $item) {
            $message = $item['commit']['message'];

            $id = $item['sha'];
            $title = htmlspecialchars(self::cutTitle($message));
            $content = nl2br(htmlspecialchars($message), false);
            $thumbnail = null;
            $icon = null;
            $link = $item['html_url'];
            // Appears to be ISO 8601.
            $date = new \DateTimeImmutable($item['commit']['author']['date']);
            $author = $item['commit']['author']['name'];

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

    public function destroy(): void {
        unset($this->items);
        $this->items = [];
    }

    /**
     * cut title after X chars (from the first line)
     *
     * @param string $title title
     * @param int $cutafter Cut after X chars
     *
     * @return string Cutted title
     */
    public static function cutTitle(string $title, int $cutafter = 69): string {
        $title = strtok($title, "\n");
        if (($cutafter > 0) && (strlen($title) > $cutafter)) {
            return substr($title, 0, $cutafter) . '…';
        }

        return $title;
    }
}
