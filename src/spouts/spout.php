<?php

namespace spouts;

/**
 * This abstract class defines the interface of a spout (source or plugin)
 * template pattern
 *
 * @template ItemExtraData of mixed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
abstract class spout {
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
     * See \spouts\rss\heise class for an example.
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

    /**
     * loads content for given source
     *
     * @param array $params params of this source
     *
     * @throws \GuzzleHttp\Exception\GuzzleException When an error is encountered
     */
    abstract public function load(array $params): void;

    /**
     * returns the xml feed url for the source
     *
     * @param array $params params for the source
     *
     * @return ?string url as xml
     */
    public function getXmlUrl(array $params): ?string {
        return null;
    }

    /**
     * returns the global html url for the source
     *
     * @return ?string url as html
     */
    abstract public function getHtmlUrl(): ?string;

    /**
     * Returns the spout title
     *
     * @return ?string title as loaded by the spout
     */
    public function getTitle(): ?string {
        return null;
    }

    /**
     * Returns the icon common to this source.
     *
     * @return ?string icon as URL
     */
    public function getIcon(): ?string {
        return null;
    }

    /**
     * Returns list of items.
     *
     * @return iterable<Item<ItemExtraData>> list of items
     */
    abstract public function getItems(): iterable;

    /**
     * destroy the plugin (prevent memory issues)
     */
    public function destroy(): void {
    }
}
