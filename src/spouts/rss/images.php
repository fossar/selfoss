<?php

declare(strict_types=1);

namespace spouts\rss;

use SimplePie;
use spouts\Item;

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
     * @return \Generator<Item<SimplePie\Item>> list of items
     */
    public function getItems(): iterable {
        foreach (parent::getItems() as $item) {
            $thumbnail = $this->findThumbnail($item->getExtraData());
            if ($thumbnail !== null) {
                yield $item->withThumbnail($thumbnail);
            } else {
                yield $item;
            }
        }
    }

    private function findThumbnail(SimplePie\Item $item): ?string {
        // search enclosures (media tags)
        if (($firstEnclosure = $item->get_enclosure(0)) !== null) {
            // thumbnail given?
            if ($firstEnclosure->get_thumbnail()) {
                return $firstEnclosure->get_thumbnail();
            }

            // link given?
            elseif ($firstEnclosure->get_link()) {
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
