<?php

// SPDX-FileCopyrightText: © 2022 Jan Tojnar
// SPDX-License-Identifier: GPL-3.0-or-later

namespace spouts\rss;

use RssBridge\BridgeFactory;
use spouts\Item;

/**
 * Spout for obtaining a feed from a site using RSS-Bridge.
 */
class RssBridge extends \spouts\spout {
    /** @var string name of source */
    public $name = 'Random websites (using RSS-Bridge)';

    /** @var string description of this source type */
    public $description = 'Extract feed from website that might not have RSS feed.';

    /** @var array configurable parameters */
    public $params = [
        'url' => [
            'title' => 'URL',
            'type' => 'url',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
        // TODO: Allow selecting bridge rather than relying on detection
        // Will need to convert the parameter model.
    ];

    /** @var ?string URL of the source */
    protected $htmlUrl = null;

    /** @var ?string URL of the source icon */
    protected $sourceIcon = null;

    public function __construct(
        private BridgeFactory $bridgeFactory,
        private Logger $logger,
    ) {
    }

    //
    // Source Methods
    //

    public function load(array $params) {
        // TODO: Implement this method upstream.
        $url = htmlspecialchars_decode($params['url']);
        $bridge = $this->bridgeFactory->detect($url);
        $this->logger->debug('Loading feed for “{$url}”');
        $bridge->collectData();
        $items = $bridge->getItems();

        // TODO: Remove legacy (array) items in favour of FeedItem objects upstream.

        $this->items = new ArrayIterator($items);
        $this->htmlUrl = $bridge->getURI();
        $this->sourceIcon = $bridge->getIcon();
        $this->spoutTitle = $bridge->getName();
        // TODO: Implement support for donation link.
    }

    /**
     * @return ?string
     */
    public function getHtmlUrl() {
        return $this->htmlUrl;
    }

    public function getIcon() {
        return $this->sourceIcon;
    }

    /**
     * @return \Generator<Item<SimplePie_Item>> list of items
     */
    public function getItems() {
        foreach ($this->items as $item) {
            $id = $item->getUid();
            $title = $item->getTitle();
            $content = $item->getContent();
            $thumbnail = null;
            // Per item icons not supported.
            $icon = null;
            $link = $item->getURI();
            $unixDate = $item->getTimestamp();
            $date = $unixDate !== null ? new \DateTimeImmutable('@' . $unixDate) : new \DateTimeImmutable();
            $author = $item->getAuthor();

            yield new Item(
                $id,
                $title,
                $content,
                $thumbnail,
                $icon,
                $link,
                $date,
                $author,
                $item
            );
        }
    }
}
