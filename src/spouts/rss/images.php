<?php

namespace spouts\rss;

/**
 * Spout for fetching images from given rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class images extends feed {
    /** @var string name of spout */
    public $name = 'RSS Feed Images';

    /** @var string description of this source type */
    public $description = 'Fetch images from given rss feed.';

    /**
     * returns the thumbnail of this item (for multimedia feeds)
     *
     * @return mixed thumbnail data
     */
    public function getThumbnail() {
        if ($this->items === null || $this->valid() === false) {
            return '';
        }

        $item = current($this->items);

        // search enclosures (media tags)
        if (count(@$item->get_enclosures()) > 0) {
            // thumbnail given?
            if (@$item->get_enclosure(0)->get_thumbnail()) {
                return @$item->get_enclosure(0)->get_thumbnail();
            }

            // link given?
            elseif (@$item->get_enclosure(0)->get_link()) {
                return @$item->get_enclosure(0)->get_link();
            }
        } else { // no enclosures: search image link in content
            $image = \helpers\Image::findFirstImageSource(@$item->get_content());
            if ($image !== null) {
                return $image;
            }
        }

        return '';
    }
}
