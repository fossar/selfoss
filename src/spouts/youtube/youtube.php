<?php

namespace spouts\youtube;

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

    /** @var array configurable parameters */
    public $params = [
        'channel' => [
            'title' => 'URL or username',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
    ];

    public function load(array $params) {
        $url = $this->getXmlUrl($params);
        parent::load(['url' => $url]);
    }

    /**
     * @return string
     */
    public function getXmlUrl(array $params) {
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
        } else {
            $id = $urlOrUsername;
            $feed_type = 'user';
        }

        return 'https://www.youtube.com/feeds/videos.xml?' . $feed_type . '=' . $id;
    }

    public function getThumbnail() {
        if (!$this->valid()) {
            return '';
        }

        $item = $this->items->current();

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
