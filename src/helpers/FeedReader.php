<?php

declare(strict_types=1);

namespace helpers;

use Psr\SimpleCache\CacheInterface;
use SimplePie\SimplePie;

/**
 * Helper class for obtaining feeds
 */
class FeedReader {
    private SimplePie $simplepie;

    public function __construct(
        SimplePie $simplepie,
        WebClient $webClient,
        ?CacheInterface $cache = null
    ) {
        $this->simplepie = $simplepie;

        // initialize simplepie feed loader
        if ($cache !== null) {
            $this->simplepie->set_cache($cache);
        }

        // abuse set_curl_options since there is no sane way to pass data to SimplePie\File
        $this->simplepie->set_curl_options([
            WebClient::class => $webClient,
        ]);

        $this->simplepie->get_registry()->register(\SimplePie\File::class, SimplePieFileGuzzle::class, true);
        $this->simplepie->set_autodiscovery_level(SimplePie::LOCATOR_AUTODISCOVERY | SimplePie::LOCATOR_LOCAL_EXTENSION | SimplePie::LOCATOR_LOCAL_BODY);
        $this->simplepie->set_useragent($webClient->getUserAgent());
    }

    /**
     * Load the feed for provided URL using SimplePie.
     *
     * @param string $url URL of the feed
     *
     * @return array{items: \SimplePie\Item[], htmlUrl: string, title: ?string}
     */
    public function load(string $url): array {
        @$this->simplepie->set_feed_url($url);
        // fetch items
        @$this->simplepie->init();

        // on error retry with force_feed
        // @phpstan-ignore-next-line notIdentical.alwaysTrue (The upstream type is incorrect: https://github.com/simplepie/simplepie/pull/903)
        if ($this->simplepie->error() !== null) {
            @$this->simplepie->set_autodiscovery_level(SimplePie::LOCATOR_NONE);
            @$this->simplepie->force_feed(true);
            @$this->simplepie->init();
        }

        // check for error
        /** @var ?string $error */ // For PHPStan: This can only be an array in multifeed mode, which we do not use.
        $error = $this->simplepie->error();
        if ($error !== null) {
            throw new \Exception($error);
        }

        /** @var \SimplePie\Item[] $items */ // SimplePie considers this nullable for some reason.
        $items = $this->simplepie->get_items();

        return [
            // save fetched items
            'items' => $items,
            'htmlUrl' => htmlspecialchars_decode((string) $this->simplepie->get_link(), ENT_COMPAT), // SimplePie sanitizes URLs
            // Atom feeds can contain HTML in titles, strip tags and convert to text.
            'title' => htmlspecialchars_decode(strip_tags($this->simplepie->get_title() ?? '')),
        ];
    }

    /**
     * Get the URL of the feed’s logo.
     */
    public function getImageUrl(): ?string {
        $raw = $this->simplepie->get_image_url();

        return $raw === null ? $raw : htmlspecialchars_decode($raw, ENT_COMPAT); // SimplePie sanitizes URLs
    }

    /**
     * Get the URL of the feed
     */
    public function getFeedUrl(): ?string {
        // SimplePie sanitizes URLs but it unescapes ampersands here.
        // Since double quotes and angle brackets are excluded from URIs,
        // we need not worry about them and consider this unescaped.
        // https://tools.ietf.org/html/rfc2396#section-2.4.3
        return $this->simplepie->subscribe_url();
    }

    public function __destruct() {
        $this->simplepie->__destruct();
    }
}
