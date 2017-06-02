<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news from Teltarif with the full text
 *
 * @copyright  Copyright (c) Martin Sauter (http://www.wirelessmoves.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Martin Sauter  <martin.sauter@wirelessmoves.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class teltarif extends fulltextrss {
    /** @var string name of spout */
    public $name = 'News: Teltarif';

    /** @var string description of this source type */
    public $description = 'This feed fetches Telarif news with full content (not only the header as content)';

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
    public $params = false;

    /**
     * addresses of feeds for the sections
     */
    private $feedUrl = 'http://www.teltarif.de/feed/news/20.rss2';

    /**
     * loads content for given source
     *
     * @param string $url
     *
     * @return void
     */
    public function load($params) {
        parent::load(['url' => $this->getXmlUrl()]);
    }

    /**
     * returns the xml feed url for the source
     *
     * @param mixed $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl($params = null) {
        return $this->feedUrl;
    }
}
