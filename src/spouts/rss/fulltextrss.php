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
    /** @var string name of spout */
    public $name = 'RSS Feed (with content extraction)';

    /** @var string description of this source type */
    public $description = 'Use “Graby” library to get full content of feed posts instead of partial content provided by some websites.';

    /** @var SpoutParameters configurable parameters */
    public $params = [
        'url' => [
            'title' => 'URL',
            'type' => Parameter::TYPE_URL,
            'default' => '',
            'required' => true,
            'validation' => [Parameter::VALIDATION_NONEMPTY],
        ],
    ];

    /** @var string tag for logger */
    private static $loggerTag = 'selfoss.graby';

    /** @var Configuration configuration */
    private $configuration;

    /** @var ?Graby */
    private $graby = null;

    /** @var Logger */
    private $logger;

    /** @var WebClient */
    private $webClient;

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
        foreach (parent::getItems() as $item) {
            $url = (string) self::removeTrackersFromUrl(new Uri($item->getLink()));
            yield $item->withLink($url)->withContent(function(Item $item) use ($url): HtmlString {
                return $this->getFullContent($url, $item);
            });
        }
    }

    /**
     * @param Item<SimplePie\Item> $item
     */
    public function getFullContent(string $url, Item $item): HtmlString {
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

            return HtmlString::fromRaw('<p><strong>Failed to get web page</strong></p>' . $item->getContent()->getRaw());
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
                function(string $param): bool {
                    return !str_starts_with($param, 'utm_');
                }
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
