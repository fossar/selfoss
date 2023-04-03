<?php

declare(strict_types=1);

namespace spouts\tumblr;

use spouts\Parameter;

/**
 * Spout for fetching an tumblr user
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class user extends \spouts\rss\images {
    public string $name = 'tumblr: user posts';

    public string $description = 'Get posts of a tumblr user.';

    public array $params = [
        'username' => [
            'title' => 'Username',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
    ];

    public function load(array $params): void {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params): string {
        return 'http://' . urlencode($params['username']) . '.tumblr.com/rss';
    }
}
