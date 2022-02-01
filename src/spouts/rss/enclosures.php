<?php

namespace spouts\rss;

use SimplePie_Item;
use spouts\Item;

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
    public $description = 'Get posts from RSS feed, including media enclosures.';

    /**
     * @return \Generator<Item<SimplePie_Item>> list of items
     */
    public function getItems() {
        foreach (parent::getItems() as $item) {
            $newContent = $this->getContentWithEnclosures($item->getContent(), $item->getExtraData());
            yield $item->withContent($newContent);
        }
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function getContentWithEnclosures($content, SimplePie_Item $item) {
        $enclosures = $item->get_enclosures();
        if ($enclosures === null) {
            return $content;
        }

        $newContent = $content;

        foreach ($enclosures as $enclosure) {
            if ($enclosure->get_medium() === 'image') {
                $title = htmlspecialchars(strip_tags((string) $enclosure->get_title()));
                $newContent .= '<img src="' . $enclosure->get_link() . '" alt="' . $title . '" title="' . $title . '" />';
            }
        }

        return $newContent;
    }
}
