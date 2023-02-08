<?php

declare(strict_types=1);

namespace spouts\twitter;

use spouts\Parameter;

/**
 * Spout for fetching the twitter timeline of your twitter account
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class hometimeline extends \spouts\twitter\usertimeline {
    /** @var string name of source */
    public $name = 'Twitter: your timeline';

    /** @var string description of this source type */
    public $description = 'Fetch your twitter timeline.';

    /** @var SpoutParameters configurable parameters */
    public $params = [
        'consumer_key' => [
            'title' => 'Consumer Key',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'consumer_secret' => [
            'title' => 'Consumer Secret',
            'type' => Parameter::TYPE_PASSWORD,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'access_key' => [
            'title' => 'Access Key',
            'type' => Parameter::TYPE_PASSWORD,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
        'access_secret' => [
            'title' => 'Access Secret',
            'type' => Parameter::TYPE_PASSWORD,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
    ];

    public function load(array $params): void {
        $this->client = $this->getHttpClient($params['consumer_key'], $params['consumer_secret'], $params['access_key'], $params['access_secret']);

        $this->items = $this->fetchTwitterTimeline('statuses/home_timeline');

        $this->htmlUrl = 'https://twitter.com/';

        $this->title = 'Home timeline';
    }
}
