<?php

declare(strict_types=1);

namespace spouts\youtube;

use SimplePie;
use spouts\Item;
use spouts\Parameter;

/**
 * Spout for fetching a YouTube rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @copywork   Arndt Staudinger <info@clucose.com> April 2013
 */
class youtube extends \spouts\rss\feed {
    /** @var string name of source */
    public $name = 'YouTube';

    /** @var string description of this source type */
    public $description = 'Follow videos from a YouTube channel or a playlist.';

    /** @var SpoutParameters configurable parameters */
    public $params = [
        'channel' => [
            'title' => 'URL or username',
            'type' => Parameter::TYPE_TEXT,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
    ];

    public function load(array $params): void {
        $url = $this->getXmlUrl($params);
        parent::load(['url' => $url]);
    }

    public function getXmlUrl(array $params): string {
        $urlOrUsername = $params['channel'];
        if (preg_match('(^https?://www.youtube.com/channel/([a-zA-Z0-9_-]+)$)', $urlOrUsername, $matched)) {
            $id = $matched[1];
            $feed_type = 'channel_id';
        } elseif (preg_match('(^https?://www.youtube.com/user/([a-zA-Z0-9_]+)$)', $urlOrUsername, $matched)) {
            $id = $matched[1];
            $feed_type = 'user';
        } elseif (preg_match('(^https?://www.youtube.com/([a-zA-Z0-9_]+)$)', $urlOrUsername, $matched)) {
            $id = $matched[1];
            $feed_type = 'user';
        } elseif (preg_match('(^https?://www.youtube.com/playlist\?list=([a-zA-Z0-9_]+)$)', $urlOrUsername, $matched)) {
            $id = $matched[1];
            $feed_type = 'playlist_id';
        } elseif (preg_match('(^@)', $urlOrUsername)) {
            // https://www.youtube.com/handle
            // Rely on feed discovery.
            return 'https://www.youtube.com/' . $urlOrUsername;
        } elseif (preg_match('(^https?://www.youtube.com/)', $urlOrUsername)) {
            // Rely on feed discovery.
            return $urlOrUsername;
        } else {
            $id = $urlOrUsername;
            $feed_type = 'user';
        }

        return 'https://www.youtube.com/feeds/videos.xml?' . $feed_type . '=' . $id;
    }

    /**
     * @return \Generator<Item<SimplePie\Item>>
     */
    public function getItems(): iterable {
        foreach (parent::getItems() as $item) {
            $thumbnail = $this->getThumbnail($item->getExtraData());
            yield $item->withThumbnail($thumbnail);
        }
    }

    private function getThumbnail(SimplePie\Item $item): ?string {
        // search enclosures (media tags)
        if (($firstEnclosure = $item->get_enclosure(0)) !== null) {
            if ($firstEnclosure->get_thumbnail()) {
                // thumbnail given
                return $firstEnclosure->get_thumbnail();
            } elseif ($firstEnclosure->get_link()) {
                // link given
                return $firstEnclosure->get_link();
            }
        } else { // no enclosures: search image link in content
            $image = \helpers\ImageUtils::findFirstImageSource((string) $item->get_content());
            if ($image !== null) {
                return $image;
            }
        }

        return null;
    }
}
