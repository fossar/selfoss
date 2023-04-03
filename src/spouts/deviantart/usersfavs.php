<?php

declare(strict_types=1);

namespace spouts\deviantart;

use spouts\Parameter;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class usersfavs extends \spouts\rss\images {
    public string $name = 'DeviantArt: user favs';

    public string $description = 'Get favorites of a DeviantArt user.';

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
        return 'https://backend.deviantart.com/rss.xml?q=%20sort%3Atime%20favby%3A' . urlencode($params['username']) . '&type=deviation';
    }
}
