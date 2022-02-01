<?php

namespace helpers;

use SimplePie\SimplePie;

/**
 * Helper class for obtaining feeds
 */
class FeedReader {
    /** @var SimplePie */
    private $simplepie;

    /**
     * @param ?string $cacheLocation
     * @param int $cacheTimeout
     */
    public function __construct(SimplePie $simplepie, WebClient $webClient, $cacheLocation = null, $cacheTimeout = 1800) {
        $this->simplepie = $simplepie;

        // initialize simplepie feed loader
        if ($cacheLocation !== null) {
            $this->simplepie->set_cache_location($cacheLocation);
            $this->simplepie->set_cache_duration($cacheTimeout);
        }

        // abuse set_curl_options since there is no sane way to pass data to SimplePie\File
        $this->simplepie->set_curl_options([
            WebClient::class => $webClient,
        ]);

        $this->simplepie->set_file_class(SimplePieFileGuzzle::class);
        $this->simplepie->set_autodiscovery_level(SIMPLEPIE_LOCATOR_AUTODISCOVERY | SIMPLEPIE_LOCATOR_LOCAL_EXTENSION | SIMPLEPIE_LOCATOR_LOCAL_BODY);
        $this->simplepie->set_useragent($webClient->getUserAgent());
    }

    /**
     * Load the feed for provided URL using SimplePie.
     *
     * @param string $url URL of the feed
     *
     * @return array{items: \SimplePie_Item[], htmlUrl: string, title: ?string}
     */
    public function load($url) {
        @$this->simplepie->set_feed_url($url);
        // fetch items
        @$this->simplepie->init();

        // on error retry with force_feed
        if (@$this->simplepie->error()) {
            @$this->simplepie->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
            @$this->simplepie->force_feed(true);
            @$this->simplepie->init();
        }

        // check for error
        if (@$this->simplepie->error()) {
            throw new \Exception($this->simplepie->error());
        }

        return [
            // save fetched items
            'items' => $this->simplepie->get_items(),
            'htmlUrl' => htmlspecialchars_decode((string) $this->simplepie->get_link(), ENT_COMPAT), // SimplePie sanitizes URLs
            'title' => $this->simplepie->get_title(),
        ];
    }

    /**
     * Get the URL of the feed’s logo.
     *
     * @return ?string
     */
    public function getImageUrl() {
        $raw = $this->simplepie->get_image_url();

        return $raw === null ? $raw : htmlspecialchars_decode($raw, ENT_COMPAT); // SimplePie sanitizes URLs
    }

    /**
     * Get the URL of the feed
     *
     * @return ?string
     */
    public function getFeedUrl() {
        // SimplePie sanitizes URLs but it unescapes ampersands here.
        // Since double quotes and angle brackets are excluded from URIs,
        // we need not worry about them and consider this unescaped.
        // https://tools.ietf.org/html/rfc2396#section-2.4.3
        return $this->simplepie->subscribe_url();
    }

    /**
     * @return void
     */
    public function __destruct() {
        $this->simplepie->__destruct();
    }
}
