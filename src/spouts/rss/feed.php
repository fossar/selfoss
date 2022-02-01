<?php

namespace spouts\rss;

use helpers\FeedReader;
use helpers\Image;
use Monolog\Logger;
use SimplePie_Item;
use spouts\Item;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class feed extends \spouts\spout {
    /** @var string name of source */
    public $name = 'RSS Feed';

    /** @var string description of this source type */
    public $description = 'Get posts from plain RSS/Atom feed.';

    /** @var array configurable parameters */
    public $params = [
        'url' => [
            'title' => 'URL',
            'type' => 'url',
            'default' => '',
            'required' => true,
            'validation' => ['notempty'],
        ],
    ];

    /** @var ?string URL of the source */
    protected $htmlUrl = null;

    /** @var Logger */
    private $logger;

    /** @var FeedReader */
    private $feed;

    /** @var Image image helper */
    private $imageHelper;

    /** @var ?string title of the source */
    protected $title = null;

    /** @var SimplePie_Item[] current fetched items */
    private $items = [];

    public function __construct(FeedReader $feed, Image $imageHelper, Logger $logger) {
        $this->imageHelper = $imageHelper;
        $this->logger = $logger;
        $this->feed = $feed;
    }

    //
    // Source Methods
    //

    public function load(array $params) {
        $feedData = $this->feed->load(htmlspecialchars_decode($params['url']));
        $this->items = $feedData['items'];
        $this->htmlUrl = $feedData['htmlUrl'];
        $this->title = $feedData['title'];
    }

    /**
     * @return ?string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return ?string
     */
    public function getXmlUrl(array $params) {
        return isset($params['url']) ? html_entity_decode($params['url']) : null;
    }

    /**
     * @return ?string
     */
    public function getHtmlUrl() {
        return $this->htmlUrl;
    }

    /**
     * @return \Generator<Item<SimplePie_Item>> list of items
     */
    public function getItems() {
        foreach ($this->items as $item) {
            $id = (string) $item->get_id();
            if (strlen($id) > 255) {
                $id = md5($id);
            }
            $title = htmlspecialchars_decode((string) $item->get_title());
            $content = (string) $item->get_content();
            $thumbnail = null;
            $icon = null;
            $link = htmlspecialchars_decode((string) $item->get_link(), ENT_COMPAT); // SimplePie sanitizes URLs
            $unixDate = $item->get_date('U');
            $date = $unixDate !== null ? new \DateTimeImmutable('@' . $unixDate) : new \DateTimeImmutable();
            $author = $this->getAuthorString($item);

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

    /**
     * @return ?string
     */
    private function getAuthorString(SimplePie_Item $item) {
        $author = $item->get_author();
        if (isset($author)) {
            $name = $author->get_name();
            if (isset($name)) {
                return htmlspecialchars_decode($name);
            } else {
                return htmlspecialchars_decode((string) $author->get_email());
            }
        }

        return null;
    }

    public function getIcon() {
        // Try to use feed logo first
        $feedLogoUrl = $this->feed->getImageUrl();
        if ($feedLogoUrl && ($iconData = $this->imageHelper->fetchFavicon($feedLogoUrl)) !== null) {
            list($faviconUrl, $iconBlob) = $iconData;

            $aspectRatio = $iconBlob->getWidth() / $iconBlob->getHeight();

            if (0.8 < $aspectRatio && $aspectRatio < 1.3) {
                $this->logger->debug('icon: using feed logo: ' . $faviconUrl);

                return $faviconUrl;
            } else {
                $this->logger->debug('icon: feed logo “' . $faviconUrl . '” not square enough with aspect ratio ' . $aspectRatio . '. Not using it.');
            }
        }

        // else fallback to the favicon of the associated web page
        $htmlUrl = $this->getHtmlUrl();
        if ($htmlUrl && ($iconData = $this->imageHelper->fetchFavicon($htmlUrl, true)) !== null) {
            list($faviconUrl, $iconBlob) = $iconData;
            $this->logger->debug('icon: using feed homepage favicon: ' . $faviconUrl);

            return $faviconUrl;
        }

        // else fallback to the favicon of the feed effective domain
        $feedUrl = $this->feed->getFeedUrl();
        if ($feedUrl && ($iconData = $this->imageHelper->fetchFavicon($feedUrl, true)) !== null) {
            list($faviconUrl, $iconBlob) = $iconData;
            $this->logger->debug('icon: using feed homepage favicon: ' . $faviconUrl);

            return $faviconUrl;
        }

        return null;
    }

    public function destroy() {
        $this->feed->__destruct();
        unset($this->items);
        $this->items = [];
    }
}
