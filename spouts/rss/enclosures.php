<?php

namespace spouts\rss;

/**
 * Plugin for fetching RSS feeds with image enclosures
 *
 * @copyright  Copyright (c) Daniel Rudolf
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Daniel Rudolf <https://daniel-rudolf.de/>
 */
class enclosures extends feed {
    /** @var string name of spout */
    public $name = 'RSS Feed (with enclosures)';

    /** @var string description of this source type */
    public $description = 'Get feed (enclosures are added at the end of the feed).';

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if ($this->items !== false && $this->valid()) {
            $content = parent::getContent();
            foreach (@current($this->items)->get_enclosures() as $enclosure) {
                if ($enclosure->get_medium() == 'image') {
                    $title = htmlspecialchars(strip_tags($enclosure->get_title()));
                    $content .= '<img src="' . $enclosure->get_link() . '" alt="' . $title . '" title="' . $title . '" />';
                }
            }

            return $content;
        }

        return parent::getContent();
    }
}
