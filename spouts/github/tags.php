<?php

namespace spouts\github;

use helpers\WebClient;

/**
 * Spout for tracking tags on GitHub.
 *
 * This spout is loosely based on the "commits" spout.
 *
 * @copyright Copyright Bert Peters (http://bertptrs.nl)
 * @license GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author Bert Peters <bert@bertptrs.nl>
 */
class tags extends commits {
    /** @var string name of source */
    public $name = 'GitHub tags';

    /** @var string description of this source type */
    public $description = 'List tags on a repository';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
     *
     * e.g.
     * array(
     *   "id" => array(
     *     "title"      => "URL",
     *     "type"       => "text",
     *     "default"    => "",
     *     "required"   => true,
     *     "validation" => array("alnum")
     *   ),
     *   ....
     * )
     *
     * @var bool|mixed
     */
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
        'maxTags' => [
            'title' => 'Tags retrieved',
            'type' => 'text',
            'default' => 5,
            'required' => true,
            'validation' => ['int'],
        ]
    ];

    protected $paramValues = null;

    public function load($params) {
        $strParams = [$params['owner'], $params['repo'], $params['maxTags']];
        $this->htmlUrl = vsprintf('https://github.com/%s/%s/tags', $strParams);
        $this->spoutTitle = vsprintf('Recent tags for %s/%s', $strParams);
        $this->paramValues = $params;

        $jsonUrl = vsprintf('https://api.github.com/repos/%s/%s/tags?per_page=%u', $strParams);

        $this->items = json_decode(WebClient::request($jsonUrl), true);
    }

    /**
     * Return a unique identifier for the current item
     *
     * @return bool|string SHA hash of the item
     */
    public function getId() {
        if ($this->items !== false && $this->valid()) {
            return @current($this->items)['commit']['sha'];
        }

        return false;
    }

    /**
     * returns the current title as string
     *
     * @return string name of the tag
     */
    public function getTitle() {
        if ($this->items !== false && $this->valid()) {
            $titleParams = [
                $this->paramValues['owner'],
                $this->paramValues['repo'],
                @current($this->items)['name'],
            ];

            $message = vsprintf('%s/%s: %s', $titleParams);

            return htmlspecialchars(self::cutTitle($message));
        }

        return false;
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if ($this->items !== false && $this->valid()) {
            $item = @current($this->items);

            return <<<EOD
<a href="{$item['zipball_url']}">Zip archive</a><br>
<a href="{$item['tarball_url']}">Tar archive</a>
EOD;
        }

        return false;
    }

    /**
     * returns the link of this item
     *
     * @return string link to the release page, by lack of a better destination
     */
    public function getLink() {
        if ($this->items !== false && $this->valid()) {
            $urlParams = [
                $this->paramValues['owner'],
                $this->paramValues['repo'],
                urlencode(@current($this->items)['name']),
            ];

            return vsprintf('https://github.com/%s/%s/releases/tag/%s', $urlParams);
        }

        return false;
    }

    /**
     * Get the date for the current item.
     *
     * This method unfortunately does an additional request per item date.
     * We remedy this by only requesting the most recent 5 tags, which
     * limits the amount of additional requests, and since we only need
     * to request the date for tags not in the database, the impact is
     * lesser still.
     *
     * @return string
     */
    public function getDate() {
        $dateFormat = 'Y-m-d H:i:s';
        if ($this->items !== false && $this->valid()) {
            $commitUrl = @current($this->items)['commit']['url'];
            $commitData = json_decode(WebClient::request($commitUrl));

            return date($dateFormat, strtotime($commitData->commit->author->date));
        }

        return date($dateFormat);
    }
}
