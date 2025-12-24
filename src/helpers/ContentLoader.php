<?php

declare(strict_types=1);

namespace helpers;

use helpers\Filters\AcceptingFilter;
use helpers\Filters\FilterFactory;
use helpers\Filters\FilterSyntaxError;
use Monolog\Logger;

/**
 * Helper class for loading extern items
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
final class ContentLoader {
    public const ICON_FORMAT = Image::FORMAT_PNG;
    public const THUMBNAIL_FORMAT = Image::FORMAT_JPEG;

    public function __construct(
        private Configuration $configuration,
        private \daos\DatabaseInterface $database,
        private IconStore $iconStore,
        private Image $imageHelper,
        private \daos\Items $itemsDao,
        private Logger $logger,
        private \daos\Sources $sourcesDao,
        private SpoutLoader $spoutLoader,
        private ThumbnailStore $thumbnailStore,
        private WebClient $webClient
    ) {
    }

    /**
     * updates all sources
     */
    public function update(UpdateVisitor $updateVisitor): void {
        $count = $this->sourcesDao->count();
        $updateVisitor->started($count);

        $sources = $this->sourcesDao->getByLastUpdate();
        foreach ($sources as $source) {
            $this->fetch($source);
            $updateVisitor->sourceUpdated();
        }
        $this->cleanup();
        $updateVisitor->finished();
    }

    /**
     * updates single source
     *
     * @param int $id id of the source to update
     *
     * @throws FileNotFoundException it there is no source with the id
     */
    public function updateSingle(int $id): void {
        $source = $this->sourcesDao->get($id);
        if ($source) {
            $this->fetch($source);
            $this->cleanup();
        } else {
            throw new FileNotFoundException("Unknown source: $id");
        }
    }

    /**
     * updates a given source
     * returns an error or true on success
     *
     * @param mixed $source the current source
     */
    public function fetch(mixed $source): void {
        $lastEntry = $source['lastentry'];

        // at least 20 seconds wait until next update of a given source
        $this->updateSource($source, null);
        if (time() - $source['lastupdate'] < 20) {
            $this->logger->debug('Source ' . $source['title'] . ' updated less then 20 seconds ago, skipping.');

            return;
        }

        @set_time_limit(5000);
        error_reporting(E_ERROR);

        // logging
        $this->logger->debug('---');
        $this->logger->debug('start fetching source "' . $source['title'] . ' (id: ' . $source['id'] . ') ');

        // get spout
        $spout = $this->spoutLoader->get($source['spout']);
        if ($spout === null) {
            $this->logger->error('unknown spout: ' . $source['spout']);
            $this->sourcesDao->error($source['id'], 'unknown spout');

            return;
        }
        $this->logger->debug('spout successfully loaded: ' . $source['spout']);

        // receive content
        $this->logger->debug('fetch content');
        try {
            $spout->load(
                json_decode(html_entity_decode($source['params']), true)
            );

            // current date
            $minDate = null;
            if ($this->configuration->itemsLifetime !== 0) {
                $minDate = new \DateTime();
                $minDate->sub(new \DateInterval('P' . $this->configuration->itemsLifetime . 'D'));
                $this->logger->debug('minimum date: ' . $minDate->format('Y-m-d H:i:s'));
            }

            // insert new items in database
            $this->logger->debug('start item fetching');

            // Spout iterator can be a generator so we cannot iterate it twice.
            $items = $spout->getItems();
            if (!is_array($items)) {
                $items = iterator_to_array($items);
            }

            $itemsInFeed = [];
            foreach ($items as $item) {
                $itemsInFeed[] = $item->getId();
            }
            $itemsFound = $this->itemsDao->findAll($itemsInFeed, $source['id']);

            $iconCache = [];
            $sourceIconUrl = null;
            $itemsSeen = [];

            $filterExpression = trim($source['filter'] ?? '');
            try {
                $filter = FilterFactory::fromString($filterExpression);
            } catch (FilterSyntaxError $exception) {
                $this->logger->error('filter error: ' . $exception->getMessage());
                $filter = new AcceptingFilter();
            }

            foreach ($items as $item) {
                $titlePlainText = $item->getTitle()->getPlainText();
                // item already in database?
                if (isset($itemsFound[$item->getId()])) {
                    $this->logger->debug('item "' . $titlePlainText . '" already in database.');
                    $itemsSeen[] = $itemsFound[$item->getId()];
                    continue;
                }

                // test date: continue with next if item too old
                $itemDate = $item->getDate();
                if ($itemDate === null) {
                    $itemDate = new \DateTimeImmutable();
                }
                if ($minDate !== null && $itemDate < $minDate) {
                    $this->logger->debug('item "' . $titlePlainText . '" (' . $itemDate->format(\DateTime::ATOM) . ') older than ' . $this->configuration->itemsLifetime . ' days');
                    continue;
                }

                // date in future? Set current date
                $now = new \DateTimeImmutable();
                if ($itemDate > $now) {
                    $itemDate = $now;
                }

                // insert new item
                $this->logger->debug('start insertion of new item "' . $titlePlainText . '"');

                try {
                    // fetch content
                    $content = $item->getContent();

                    // sanitize content html
                    $content = $this->sanitizeContent($content);
                } catch (\Throwable $e) {
                    $content = HtmlString::fromPlainText('Error: Content not fetched. Reason: ' . $e->getMessage());
                    $this->logger->error('Can not fetch "' . $titlePlainText . '"', ['exception' => $e]);
                }

                // sanitize title
                $title = HtmlString::fromRaw(trim($this->sanitizeField($item->getTitle())->getRaw()));

                // Check sanitized title against filter
                if (!$filter->admits($item->withTitle($title)->withContent($content))) {
                    continue;
                }

                $this->logger->debug('item content sanitized');

                $newItem = [
                    'title' => $title,
                    'content' => $content,
                    'source' => $source['id'],
                    'datetime' => $itemDate,
                    'uid' => $item->getId(),
                    'link' => htmLawed($item->getLink(), ['deny_attribute' => '*', 'elements' => '-*']),
                    'author' => $item->getAuthor(),
                    'thumbnail' => null,
                    'icon' => null,
                ];

                $thumbnailUrl = $item->getThumbnail();
                if ($thumbnailUrl !== null) {
                    // save thumbnail
                    $newItem['thumbnail'] = $this->fetchThumbnail($thumbnailUrl) ?: '';
                }

                try {
                    // Clear the value in case we need it in catch clause.
                    $iconUrl = null;
                    $iconUrl = $item->getIcon();
                    if ($iconUrl !== null) {
                        if (isset($iconCache[$iconUrl])) {
                            $this->logger->debug('reusing recently used icon: ' . $iconUrl);
                        } else {
                            // save icon
                            $iconCache[$iconUrl] = $this->fetchIcon($iconUrl) ?: '';
                        }
                        $newItem['icon'] = $iconCache[$iconUrl];
                    } elseif ($sourceIconUrl !== null) {
                        $this->logger->debug('using the source icon');
                        $newItem['icon'] = $sourceIconUrl;
                    } else {
                        try {
                            // we do not want to run this more than once
                            $sourceIconUrl = $spout->getIcon() ?: '';

                            if (strlen(trim($sourceIconUrl)) > 0) {
                                // save source icon
                                $sourceIconUrl = $this->fetchIcon($sourceIconUrl) ?: '';
                                $newItem['icon'] = $sourceIconUrl;
                            } else {
                                $this->logger->debug('no icon for this item or source');
                            }
                        } catch (\Throwable $e) {
                            // cache failure
                            $sourceIconUrl = '';
                            $this->logger->error('feed icon: error', ['exception' => $e]);
                        }
                    }
                } catch (\Throwable $e) {
                    // cache failure
                    if ($iconUrl !== null) {
                        $iconCache[$iconUrl] = '';
                    }
                    $this->logger->error('icon: error', ['exception' => $e]);
                }

                // insert new item
                $this->itemsDao->add($newItem);
                $this->logger->debug('item inserted');

                $this->logger->debug('Memory usage: ' . memory_get_usage());
                $this->logger->debug('Memory peak usage: ' . memory_get_peak_usage());

                $lastEntry = max($lastEntry, $itemDate->getTimestamp());
            }
        } catch (\Throwable $e) {
            $this->logger->error('error loading feed content for ' . $source['title'], ['exception' => $e]);
            $this->sourcesDao->error($source['id'], date('Y-m-d H:i:s') . 'error loading feed content: ' . $e->getMessage());

            return;
        }

        // destroy feed object (prevent memory issues)
        $this->logger->debug('destroy spout object');
        $spout->destroy();

        // remove previous errors and set last update timestamp
        $this->updateSource($source, $lastEntry);

        // mark items seen in the feed to prevent premature garbage removal
        if (count($itemsSeen) > 0) {
            $this->itemsDao->updateLastSeen($itemsSeen);
        }
    }

    /**
     * Sanitize content for preventing XSS attacks.
     *
     * @param HtmlString $content content of the given feed
     *
     * @return HtmlString sanitized content
     */
    protected function sanitizeContent(HtmlString $content): HtmlString {
        return HtmlString::fromRaw(htmLawed(
            $content->getRaw(),
            [
                'safe' => 1,
                'deny_attribute' => '* -alt -title -src -href -target',
                'keep_bad' => 0,
                'comment' => 1,
                'cdata' => 1,
                'elements' => 'div,p,ul,li,a,img,dl,dt,dd,h1,h2,h3,h4,h5,h6,ol,br,table,tr,td,blockquote,pre,ins,del,th,thead,tbody,b,i,strong,em,tt,sub,sup,s,strike,code',
            ],
            'img=width, height'
        ));
    }

    /**
     * Sanitize a simple field
     *
     * @param HtmlString $value content of the given field
     *
     * @return HtmlString sanitized content
     */
    protected function sanitizeField(HtmlString $value): HtmlString {
        return HtmlString::fromRaw(htmLawed(
            $value->getRaw(),
            [
                'deny_attribute' => '* -href -title -target',
                'elements' => 'a,br,ins,del,b,i,strong,em,tt,sub,sup,s,code',
            ]
        ));
    }

    /**
     * Fetch an image URL and process it as a thumbnail.
     *
     * @param string $url the thumbnail URL
     *
     * @return ?string path in the thumbnails directory
     */
    protected function fetchThumbnail(string $url): ?string {
        try {
            $data = $this->webClient->request($url);
            $format = self::THUMBNAIL_FORMAT;
            $image = $this->imageHelper->loadImage($data, $format, 500, 500);

            if ($image !== null) {
                return $this->thumbnailStore->store($url, $image->getData());
            } else {
                $this->logger->error('thumbnail generation error: ' . $url);
            }
        } catch (\Throwable $e) {
            $this->logger->error("failed to retrieve thumbnail $url,", ['exception' => $e]);

            return null;
        }

        return null;
    }

    /**
     * Fetch an image and process it as favicon.
     *
     * @param string $url icon given by the spout
     *
     * @return ?string path in the favicons directory
     */
    protected function fetchIcon(string $url): ?string {
        try {
            $data = $this->webClient->request($url);
            $format = Image::FORMAT_PNG;
            $image = $this->imageHelper->loadImage($data, $format, 30, null);

            if ($image !== null) {
                return $this->iconStore->store($url, $image->getData());
            } else {
                $this->logger->error('icon generation error: ' . $url);
            }
        } catch (\Throwable $e) {
            $this->logger->error("failed to retrieve image $url,", ['exception' => $e]);

            return null;
        }

        return null;
    }

    /**
     * Obtain title for given data
     *
     * @param array<string, mixed>&array{spout: string} $data
     */
    public function fetchTitle(array $data): ?string {
        $this->logger->debug('Start fetching spout title');

        // get spout
        $spout = $this->spoutLoader->get($data['spout']);

        if ($spout === null) {
            $this->logger->error("Unknown spout '{$data['spout']}' when fetching title");

            return null;
        }

        // receive content
        try {
            @set_time_limit(5000);
            error_reporting(E_ERROR);

            $spout->load($data);
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching title', ['exception' => $e]);

            return null;
        }

        $title = $spout->getTitle();
        $spout->destroy();

        return $title;
    }

    /**
     * clean up messages, thumbnails etc.
     */
    public function cleanup(): void {
        // cleanup orphaned and old items
        $this->logger->debug('cleanup orphaned and old items');
        $minDate = null;
        if ($this->configuration->itemsLifetime !== 0) {
            $minDate = new \DateTime();
            $minDate->sub(new \DateInterval('P' . $this->configuration->itemsLifetime . 'D'));
        }
        $this->itemsDao->cleanup($minDate);
        $this->logger->debug('cleanup orphaned and old items finished');

        // delete orphaned thumbnails
        $this->logger->debug('delete orphaned thumbnails');
        $this->thumbnailStore->cleanup(
            fn(string $file): bool => $this->itemsDao->hasThumbnail($file)
        );
        $this->logger->debug('delete orphaned thumbnails finished');

        // delete orphaned icons
        $this->logger->debug('delete orphaned icons');
        $this->iconStore->cleanup(
            fn(string $file): bool => $this->itemsDao->hasIcon($file)
        );
        $this->logger->debug('delete orphaned icons finished');

        // optimize database
        $this->logger->debug('optimize database');
        $this->database->optimize();
        $this->logger->debug('optimize database finished');
    }

    /**
     * Update source (remove previous errors, update last update)
     *
     * @param mixed $source source object
     * @param ?int $lastEntry timestamp of the newest item or NULL when no items were added
     */
    protected function updateSource(mixed $source, ?int $lastEntry): void {
        // remove previous error
        if ($source['error'] !== null) {
            $this->sourcesDao->error($source['id'], '');
        }
        // save last update
        $this->sourcesDao->saveLastUpdate($source['id'], $lastEntry);
    }
}
