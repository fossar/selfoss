<?php

declare(strict_types=1);

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
    public string $name = '[German] teltarif.de';

    public string $description = 'Fetch Telarif news with full content (not only the header as content).';

    public array $params = [];

    /**
     * addresses of feeds for the sections
     */
    private const FEED_URL = 'http://www.teltarif.de/feed/news/20.rss2';

    public function load(array $params): void {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params): string {
        return self::FEED_URL;
    }
}
