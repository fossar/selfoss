<?php

namespace spouts\rss;

use ArrayIterator;
use helpers\FeedReader;
use helpers\Image;
use Monolog\Logger;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class feed extends \spouts\spout {
    use \helpers\ItemsIterator;

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
        $this->items = new ArrayIterator($feedData['items']);
        $this->htmlUrl = $feedData['htmlUrl'];
        $this->spoutTitle = $feedData['spoutTitle'];
    }

    public function getXmlUrl(array $params) {
        return isset($params['url']) ? html_entity_decode($params['url']) : null;
    }

    public function getHtmlUrl() {
        return $this->htmlUrl;
    }

    public function getId() {
        if ($this->valid()) {
            $id = $this->items->current()->get_id();
            if (strlen($id) > 255) {
                $id = md5($id);
            }

            return $id;
        }

        return null;
    }

    public function getTitle() {
        if ($this->valid()) {
            return htmlspecialchars_decode($this->items->current()->get_title());
        }

        return null;
    }

    public function getContent() {
        if ($this->valid()) {
            return $this->items->current()->get_content();
        }

        return null;
    }

    public function getIcon() {
        return null;
    }

    public function getSourceIcon() {
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

    public function getLink() {
        if ($this->valid()) {
            $link = $this->items->current()->get_link();

            return htmlspecialchars_decode($link, ENT_COMPAT); // SimplePie sanitizes URLs
        }

        return null;
    }

    public function getDate() {
        if ($this->valid()) {
            $date = $this->items->current()->get_date('Y-m-d H:i:s');
        }
        if (strlen($date) === 0) {
            $date = date('Y-m-d H:i:s');
        }

        return $date;
    }

    public function getAuthor() {
        if ($this->valid()) {
            $author = $this->items->current()->get_author();
            if (isset($author)) {
                $name = $author->get_name();
                if (isset($name)) {
                    return htmlspecialchars_decode($name);
                } else {
                    return htmlspecialchars_decode($author->get_email());
                }
            }
        }

        return null;
    }

    public function destroy() {
        $this->feed->__destruct();
        unset($this->items);
        $this->items = null;
    }
}
