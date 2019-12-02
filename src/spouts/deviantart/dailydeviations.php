<?php

namespace spouts\deviantart;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class dailydeviations extends \spouts\rss\images {
    /** @var string name of source */
    public $name = 'DeviantArt: daily deviations';

    /** @var string description of this source type */
    public $description = 'Get daily deviations on DeviantArt.';

    /** @var array configurable parameters */
    public $params = [];

    public function load(array $params) {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params) {
        return 'https://backend.deviantart.com/rss.xml?q=special%3Add&type=deviation&offset=0';
    }
}
