<?php

declare(strict_types=1);

namespace spouts\rss;

use helpers\HtmlString;
use SimplePie;
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
     * @return \Generator<Item<SimplePie\Item>> list of items
     */
    public function getItems(): iterable {
        foreach (parent::getItems() as $item) {
            $newContent = $this->getContentWithEnclosures($item->getContent(), $item->getExtraData());
            yield $item->withContent($newContent);
        }
    }

    private function getContentWithEnclosures(HtmlString $content, SimplePie\Item $item): HtmlString {
        $enclosures = $item->get_enclosures();
        if ($enclosures === null) {
            return $content;
        }

        $newContent = $content->getRaw();

        foreach ($enclosures as $enclosure) {
            if ($enclosure->get_medium() === 'image') {
                $title = htmlspecialchars(strip_tags((string) $enclosure->get_title()), ENT_QUOTES);
                $url = htmlspecialchars_decode($enclosure->get_link() ?? '', ENT_COMPAT); // SimplePie sanitizes URLs
                $newContent .= '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="' . $title . '" title="' . $title . '" />';
            }
        }

        return HtmlString::fromRaw($newContent);
    }
}
