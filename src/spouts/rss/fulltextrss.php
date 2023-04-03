<?php

declare(strict_types=1);

namespace spouts\rss;

use Graby\Graby;
use GuzzleHttp\Psr7\Uri;
use helpers\Configuration;
use helpers\FeedReader;
use helpers\HtmlString;
use helpers\Image;
use helpers\WebClient;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Monolog\Logger;
use SimplePie;
use spouts\Item;
use spouts\Parameter;

/**
 * Plugin for fetching the news with fivefilters Full-Text RSS
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class fulltextrss extends feed {
    public string $name = 'RSS Feed (with content extraction)';

    public string $description = 'Use “Graby” library to get full content of feed posts instead of partial content provided by some websites.';

    public array $params = [
        'url' => [
            'title' => 'URL',
            'type' => Parameter::TYPE_URL,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
    ];

    /** Tag for logger */
    private static string $loggerTag = 'selfoss.graby';

    private Configuration $configuration;
    private ?Graby $graby = null;
    private Logger $logger;
    private WebClient $webClient;

    public function __construct(Configuration $configuration, FeedReader $feed, Image $imageHelper, Logger $logger, WebClient $webClient) {
        parent::__construct($feed, $imageHelper, $logger);

        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->webClient = $webClient;
    }

    /**
     * @return \Generator<Item<SimplePie\Item>> list of items
     */
    public function getItems(): iterable {
        foreach (parent::getItems() as $originalItem) {
            $url = (string) self::removeTrackersFromUrl(new Uri($originalItem->getLink()));
            yield $originalItem->withLink($url)->withContent(
                fn(Item $item): HtmlString => $this->getFullContent($item->getLink(), $originalItem)
            );
        }
    }

    /**
     * @param Item<SimplePie\Item> $originalItem
     */
    public function getFullContent(string $url, Item $originalItem): HtmlString {
        if ($this->graby === null) {
            $this->graby = new Graby([
                'extractor' => [
                    'config_builder' => [
                        'site_config' => [$this->configuration->ftrssCustomDataDir],
                    ],
                ],
            ], new GuzzleAdapter($this->webClient->getHttpClient()));
            $logger = $this->logger->withName(self::$loggerTag);
            $this->graby->setLogger($logger);
        }

        $this->logger->info('Extracting content for page: ' . $url);

        $response = $this->graby->fetchContent($url);

        if ($response['status'] !== 200) {
            $this->logger->error('Failed loading page');

            return HtmlString::fromRaw('<p><strong>Failed to get web page</strong></p>' . $originalItem->getContent()->getRaw());
        }

        $content = HtmlString::fromRaw($response['html']);

        return $content;
    }

    /**
     * remove trackers from url
     *
     * @author Jean Baptiste Favre
     */
    private static function removeTrackersFromUrl(Uri $uri): Uri {
        // Query string
        $query = $uri->getQuery();
        if ($query !== '') {
            $q_array = explode('&', $query);
            // Remove utm_* parameters
            $clean_query = array_filter(
                $q_array,
                fn(string $param): bool => !str_starts_with($param, 'utm_')
            );
            $uri = $uri->withQuery(implode('&', $clean_query));
        }
        // Fragment
        $fragment = $uri->getFragment();
        if ($fragment !== '') {
            // Remove xtor=RSS anchor
            if (str_contains($fragment, 'xtor=RSS')) {
                $uri = $uri->withFragment('');
            }
        }

        return $uri;
    }
}
