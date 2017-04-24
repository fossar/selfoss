<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news from mmospy with the full text
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class mmospy extends fulltextrss {
    /** @var string name of spout */
    public $name = '[German] mmo-spy.de';

    /** @var string description of this source type */
    public $description = 'Fetch the mmospy news with full content (not only the header as content).';

    /** @var array configurable parameters */
    public $params = [];

    /**
     * addresses of feeds for the sections
     */
    private $feedUrl = 'http://www.mmo-spy.de/misc.php?action=newsfeed';

    /**
     * loads content for given source
     *
     * @param array $params
     *
     * @return void
     */
    public function load(array $params) {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    /**
     * returns the xml feed url for the source
     *
     * @param array $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl(array $params) {
        return $this->feedUrl;
    }
}
