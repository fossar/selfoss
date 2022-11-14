<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news from Lightreading with the full text
 *
 * @copyright  Copyright (c) Martin Sauter (http://www.wirelessmoves.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Martin Sauter  <martin.sauter@wirelessmoves.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class lightreading extends fulltextrss {
    /** @var string name of spout */
    public $name = '[English] lightreading.com';

    /** @var string description of this source type */
    public $description = 'Fetch Lightreading news with full content (not only the header as content).';

    /** @var array configurable parameters */
    public $params = [];

    /**
     * addresses of feeds for the sections
     */
    private const FEED_URL = 'http://www.lightreading.com/rss_simple.asp';

    public function load(array $params): void {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params): string {
        return self::FEED_URL;
    }
}
