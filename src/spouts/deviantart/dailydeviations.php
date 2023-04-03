<?php

declare(strict_types=1);

namespace spouts\deviantart;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class dailydeviations extends \spouts\rss\images {
    public string $name = 'DeviantArt: daily deviations';

    public string $description = 'Get daily deviations on DeviantArt.';

    public array $params = [];

    public function load(array $params): void {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params): string {
        return 'https://backend.deviantart.com/rss.xml?q=special%3Add&type=deviation&offset=0';
    }
}
