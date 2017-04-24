<?php

namespace spouts;

/**
 * This abstract class defines the interface of a spout (source or plugin)
 * template pattern
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
abstract class spout implements \Iterator {
    /** @var string name of source */
    public $name = '';

    /** @var string description of this source type */
    public $description = '';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox, select
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
     *
     * When type is "select", a new entry "values" must be supplied, holding
     * key/value pairs of internal names (key) and displayed labels (value).
     * See /spouts/rss/heise for an example.
     *
     * e.g.
     * [
     *   "id" => [
     *     "title"      => "URL",
     *     "type"       => "text",
     *     "default"    => "",
     *     "required"   => true,
     *     "validation" => ["alnum"]
     *   ],
     *   ....
     * ]
     *
     * @var array
     */
    public $params = [];

    /** @var ?string title of the spout */
    protected $spoutTitle = null;

    /**
     * loads content for given source
     *
     * @param array $params params of this source
     *
     * @return void
     */
    abstract public function load(array $params);

    /**
     * returns the xml feed url for the source
     *
     * @param array $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl(array $params) {
        return false;
    }

    /**
     * returns the global html url for the source
     *
     * @return string url as html
     */
    abstract public function getHtmlUrl();

    /**
     * Returns the spout title
     *
     * @return ?string title as loaded by the spout
     */
    public function getSpoutTitle() {
        return $this->spoutTitle;
    }

    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    abstract public function getId();

    /**
     * returns the current title as string. If the spout allows HTML in the
     * title, HTML special chars are expected to be decoded by the spout (for
     * instance when the spout feed is XML).
     *
     * @return string title
     */
    abstract public function getTitle();

    /**
     * returns the content of this item as string. HTML special chars are
     * expected to be decoded by the spout (for instance when the spout
     * feed is XML).
     *
     * @return string content
     */
    public function getContent() {
        return '';
    }

    /**
     * returns the thumbnail of this item
     *
     * @return string thumbnail url
     */
    public function getThumbnail() {
        return '';
    }

    /**
     * returns the icon of this item
     *
     * @return string icon as url
     */
    abstract public function getIcon();

    /**
     * returns the link of this item
     *
     * @return string link
     */
    abstract public function getLink();

    /**
     * returns the date of this item
     *
     * @return string date
     */
    abstract public function getDate();

    /**
     * returns the author of this item with html special chars decoded if
     * applicable.
     *
     * @return ?string author
     */
    public function getAuthor() {
        return null;
    }

    /**
     * destroy the plugin (prevent memory issues)
     *
     * @return void
     */
    public function destroy() {
    }

    /**
     * returns an instance of selfoss image helper
     * for fetching favicons
     *
     * @return \helpers\Image
     */
    public function getImageHelper() {
        return new \helpers\Image();
    }
}
