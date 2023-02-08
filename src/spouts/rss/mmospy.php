<?php

declare(strict_types=1);

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

    /** @var SpoutParameters configurable parameters */
    public $params = [];

    /**
     * addresses of feeds for the sections
     */
    private const FEED_URL = 'https://www.mmo-spy.de/misc.php?action=newsfeed';

    public function load(array $params): void {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params): string {
        return self::FEED_URL;
    }
}
