<?php

namespace spouts\youtube;

/**
 * Spout for fetching a YouTube rss feed
 *
 * @package    spouts
 * @subpackage youtube
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @copywork   Arndt Staudinger <info@clucose.com> April 2013
 */
class youtube extends \spouts\rss\feed {
    /**
     * name of source
     *
     * @var string
     */
    public $name = 'YouTube Channel';

    /**
     * description of this source type
     *
     * @var string
     */
    public $description = 'A YouTube channel as source';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * @var array
     */
    public $params = array(
        'channel' => array(
            'title' => 'Channel',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => array('notempty')
        )
    );

    /**
     * loads content for given source
     *
     * @return void
     * @param array $params the params of this source
     */
    public function load($params) {
        $url = $this->getXmlUrl($params);
        parent::load(array('url' => $url));
    }

    /**
     * returns the xml feed url for the source
     *
     * @return string url as xml
     * @param mixed $params params for the source
     */
    public function getXmlUrl($params) {
        $channel = $params['channel'];
        if (preg_match('(^https?://www.youtube.com/channel/([a-zA-Z0-9_]+)$)', $params['channel'], $matched)) {
            $channel = $matched[1];
            $channel_type = 'channel_id';
        } elseif (preg_match('(^https?://www.youtube.com/([a-zA-Z0-9_]+)$)', $params['channel'], $matched)) {
            $channel = $matched[1];
            $channel_type = 'username';
        } else {
            $channel_type = 'username';
        }

        if ($channel_type === 'username') {
            return 'https://www.youtube.com/feeds/videos.xml?user=' . $channel;
        } else {
            return 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $channel;
        }
    }

    /**
     * returns the thumbnail of this item (for multimedia feeds)
     *
     * @return string|null thumbnail data
     */
    public function getThumbnail() {
        if ($this->items === false || $this->valid() === false) {
            return null;
        }

        $item = current($this->items);

        // search enclosures (media tags)
        if (count(@$item->get_enclosures()) > 0) {
            if (@$item->get_enclosure(0)->get_thumbnail()) {
                // thumbnail given
                return @$item->get_enclosure(0)->get_thumbnail();
            } elseif (@$item->get_enclosure(0)->get_link()) {
                // link given
                return @$item->get_enclosure(0)->get_link();
            }

        // no enclosures: search image link in content
        } else {
            $image = $this->getImage(@$item->get_content());
            if ($image !== false) {
                return $image;
            }
        }

        return null;
    }
}
