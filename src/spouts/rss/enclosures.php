<?php

declare(strict_types=1);

namespace spouts\rss;

use Selfoss\helpers\HtmlString;
use SimplePie;
use SimplePie\Enclosure;
use spouts\Item;

/**
 * Plugin for fetching RSS feeds with image enclosures
 *
 * @copyright  Copyright (c) Daniel Rudolf
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Daniel Rudolf <https://daniel-rudolf.de/>
 */
class enclosures extends feed {
    public string $name = 'RSS Feed (with enclosures)';

    public string $description = 'Get posts from RSS feed, including media enclosures.';

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
            $newContent .= match (self::mediaKind($enclosure)) {
                'image' => self::formatImage($enclosure),
                'audio' => self::formatAudio($enclosure),
                default => '',
            };
        }

        return HtmlString::fromRaw($newContent);
    }

    private static function mediaKind(Enclosure $enclosure): ?string {
        $medium = $enclosure->get_medium();

        if ($medium !== null) {
            return $medium;
        }

        $type = $enclosure->get_type();

        if ($type !== null) {
            [$medium] = explode('/', $type, 2);

            return $medium;
        }

        return null;
    }

    private static function formatImage(Enclosure $enclosure): string {
        $url = $enclosure->get_link();

        if ($url === null) {
            return '';
        }

        $title = htmlspecialchars(strip_tags((string) $enclosure->get_title()), ENT_QUOTES);
        $url = htmlspecialchars_decode($url, ENT_COMPAT); // SimplePie sanitizes URLs

        return '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="' . $title . '" title="' . $title . '" />';
    }

    private static function formatAudio(Enclosure $enclosure): string {
        $url = $enclosure->get_link();

        if ($url === null) {
            return '';
        }

        $title = htmlspecialchars(strip_tags((string) $enclosure->get_title()), ENT_QUOTES);
        $url = htmlspecialchars_decode($url, ENT_COMPAT); // SimplePie sanitizes URLs

        $linkLabel = 'Download';

        $duration = $enclosure->get_duration(true);
        if ($duration !== null) {
            $linkLabel .= ' (' . $duration . ')';
        }

        $link = '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">' . $linkLabel . '</a>';

        return "\n" . $link;
    }
}
