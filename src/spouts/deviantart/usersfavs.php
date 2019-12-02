<?php

namespace spouts\deviantart;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class usersfavs extends \spouts\rss\images {
    /** @var string name of source */
    public $name = 'DeviantArt: user favs';

    /** @var string description of this source type */
    public $description = 'Get favorites of a DeviantArt user.';

    /** @var array configurable parameters */
    public $params = [
        'username' => [
            'title' => 'Username',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ]
    ];

    public function load(array $params) {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params) {
        return 'https://backend.deviantart.com/rss.xml?q=%20sort%3Atime%20favby%3A' . urlencode($params['username']) . '&type=deviation';
    }
}
