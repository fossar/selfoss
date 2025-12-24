<?php

declare(strict_types=1);

namespace spouts\rss;

use helpers\FeedReader;
use helpers\HtmlString;
use helpers\Image;
use Monolog\Logger;
use SimplePie;
use spouts\Item;
use spouts\Parameter;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 *
 * @extends \spouts\spout<SimplePie\Item>
 */
class feed extends \spouts\spout {
    public string $name = 'RSS Feed';

    public string $description = 'Get posts from plain RSS/Atom feed.';

    public array $params = [
        'url' => [
            'title' => 'URL',
            'type' => Parameter::TYPE_URL,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
    ];

    /** URL of the source */
    protected ?string $htmlUrl = null;

    /** Title of the source */
    protected ?string $title = null;

    /** @var SimplePie\Item[] current fetched items */
    private array $items = [];

    public function __construct(
        private readonly FeedReader $feed,
        private readonly Image $imageHelper,
        private readonly Logger $logger
    ) {
    }

    //
    // Source Methods
    //

    public function load(array $params): void {
        $feedData = $this->feed->load(htmlspecialchars_decode($params['url']));
        $this->items = $feedData['items'];
        $this->htmlUrl = $feedData['htmlUrl'];
        $this->title = $feedData['title'];
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function getXmlUrl(array $params): ?string {
        return isset($params['url']) ? html_entity_decode($params['url']) : null;
    }

    public function getHtmlUrl(): ?string {
        return $this->htmlUrl;
    }

    /**
     * @return \Generator<Item<SimplePie\Item>> list of items
     */
    public function getItems(): iterable {
        foreach ($this->items as $item) {
            $id = (string) $item->get_id();
            if (strlen($id) > 255) {
                $id = md5($id);
            }
            $title = (string) $item->get_title();
            // Atom feeds can contain HTML in titles, strip tags and convert to text.
            $title = HtmlString::fromPlainText(htmlspecialchars_decode(strip_tags($title)));
            $content = HtmlString::fromRaw((string) $item->get_content());
            $thumbnail = null;
            $icon = null;
            $link = htmlspecialchars_decode((string) $item->get_link(), ENT_COMPAT); // SimplePie sanitizes URLs
            $unixDate = $item->get_date('U');
            $date = $unixDate !== null ? new \DateTimeImmutable('@' . $unixDate) : new \DateTimeImmutable();
            $author = $this->getAuthorString($item);

            yield new Item(
                id: $id,
                title: $title,
                content: $content,
                thumbnail: $thumbnail,
                icon: $icon,
                link: $link,
                date: $date,
                author: $author,
                extraData: $item,
            );
        }
    }

    private function getAuthorString(SimplePie\Item $item): ?string {
        $author = $item->get_author();
        if (isset($author)) {
            // Both are sanitized using SimplePie::CONSTRUCT_TEXT
            // so they are plain text strings with escaped HTML special characters.
            $name = $author->get_name();
            $email = $author->get_email();
            if ($name !== null) {
                return htmlspecialchars_decode($name);
            } elseif ($email !== null) {
                return htmlspecialchars_decode($author->get_email() ?? '');
            }
        }

        return null;
    }

    public function getIcon(): ?string {
        // Try to use feed logo first
        $feedLogoUrl = $this->feed->getImageUrl();
        if ($feedLogoUrl && ($iconData = $this->imageHelper->fetchFavicon($feedLogoUrl)) !== null) {
            [$faviconUrl, $iconBlob] = $iconData;

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
            [$faviconUrl, $iconBlob] = $iconData;
            $this->logger->debug('icon: using feed homepage favicon: ' . $faviconUrl);

            return $faviconUrl;
        }

        // else fallback to the favicon of the feed effective domain
        $feedUrl = $this->feed->getFeedUrl();
        if ($feedUrl && ($iconData = $this->imageHelper->fetchFavicon($feedUrl, true)) !== null) {
            [$faviconUrl, $iconBlob] = $iconData;
            $this->logger->debug('icon: using feed homepage favicon: ' . $faviconUrl);

            return $faviconUrl;
        }

        return null;
    }

    public function destroy(): void {
        $this->feed->__destruct();
        unset($this->items);
        $this->items = [];
    }
}
