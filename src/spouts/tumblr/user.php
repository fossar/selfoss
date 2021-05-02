<?php

namespace spouts\tumblr;

/**
 * Spout for fetching an tumblr user
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class user extends \spouts\rss\images {
    /** @var string name of source */
    public $name = 'tumblr: user posts';

    /** @var string description of this source type */
    public $description = 'Get posts of a tumblr user.';

    /** @var array configurable parameters */
    public $params = [
        'username' => [
            'title' => 'Username',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
    ];

    public function load(array $params) {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params) {
        return 'http://' . urlencode($params['username']) . '.tumblr.com/rss';
    }
}
