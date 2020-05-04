<?php

namespace helpers;

use Monolog\Logger;

/**
 * Helper class for loading extern items
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class ContentLoader {
    /** @var \daos\Database database for optimization */
    private $database;

    /** @var Image image helper */
    private $imageHelper;

    /** @var \daos\Items database access for saving new item */
    private $itemsDao;

    /** @var Logger */
    private $logger;

    /** @var \daos\Sources database access for saving source’s last update */
    private $sourcesDao;

    /** @var SpoutLoader spout loader */
    private $spoutLoader;

    /**
     * ctor
     */
    public function __construct(\daos\Database $database, Image $imageHelper, \daos\Items $itemsDao, Logger $logger, \daos\Sources $sourcesDao, SpoutLoader $spoutLoader) {
        $this->database = $database;
        $this->imageHelper = $imageHelper;
        $this->itemsDao = $itemsDao;
        $this->logger = $logger;
        $this->sourcesDao = $sourcesDao;
        $this->spoutLoader = $spoutLoader;
    }

    /**
     * updates all sources
     *
     * @return void
     */
    public function update() {
        foreach ($this->sourcesDao->getByLastUpdate() as $source) {
            $this->fetch($source);
        }
        $this->cleanup();
    }

    /**
     * updates single source
     *
     * @param $id int id of the source to update
     *
     * @throws FileNotFoundException it there is no source with the id
     *
     * @return void
     */
    public function updateSingle($id) {
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
     *
     * @return void
     */
    public function fetch($source) {
        $lastEntry = $source['lastentry'];

        // at least 20 seconds wait until next update of a given source
        $this->updateSource($source, null);
        if (time() - $source['lastupdate'] < 20) {
            return;
        }

        @set_time_limit(5000);
        @error_reporting(E_ERROR);

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
        } catch (\Exception $e) {
            $this->logger->error('error loading feed content for ' . $source['title'], ['exception' => $e]);
            $this->sourcesDao->error($source['id'], date('Y-m-d H:i:s') . 'error loading feed content: ' . $e->getMessage());

            return;
        }

        // current date
        $minDate = new \DateTime();
        $minDate->sub(new \DateInterval('P' . \F3::get('items_lifetime') . 'D'));
        $this->logger->debug('minimum date: ' . $minDate->format('Y-m-d H:i:s'));

        // insert new items in database
        $this->logger->debug('start item fetching');

        $itemsInFeed = [];
        foreach ($spout as $item) {
            $itemsInFeed[] = $item->getId();
        }
        $itemsFound = $this->itemsDao->findAll($itemsInFeed, $source['id']);

        $lasticon = null;
        $itemsSeen = [];
        foreach ($spout as $item) {
            // item already in database?
            if (isset($itemsFound[$item->getId()])) {
                $this->logger->debug('item "' . $item->getTitle() . '" already in database.');
                $itemsSeen[] = $itemsFound[$item->getId()];
                continue;
            }

            // test date: continue with next if item too old
            $itemDate = new \DateTime($item->getDate());
            // if date cannot be parsed it will default to epoch. Change to current time.
            if ($itemDate->getTimestamp() == 0) {
                $itemDate = new \DateTime();
            }
            if ($itemDate < $minDate) {
                $this->logger->debug('item "' . $item->getTitle() . '" (' . $item->getDate() . ') older than ' . \F3::get('items_lifetime') . ' days');
                continue;
            }

            // date in future? Set current date
            $now = new \DateTime();
            if ($itemDate > $now) {
                $itemDate = $now;
            }

            // insert new item
            $this->logger->debug('start insertion of new item "' . $item->getTitle() . '"');

            $content = '';
            try {
                // fetch content
                $content = $item->getContent();

                // sanitize content html
                $content = $this->sanitizeContent($content);
            } catch (\Exception $e) {
                $content = 'Error: Content not fetched. Reason: ' . $e->getMessage();
                $this->logger->error('Can not fetch "' . $item->getTitle() . '"', ['exception' => $e]);
            }

            // sanitize title
            $title = $this->sanitizeField($item->getTitle());
            if (strlen(trim($title)) === 0) {
                $title = '[' . \F3::get('lang_no_title') . ']';
            }

            // Check sanitized title against filter
            if ($this->filter($source, $title, $content) === false) {
                continue;
            }

            // sanitize author
            $author = $this->sanitizeField($item->getAuthor());

            $this->logger->debug('item content sanitized');

            $newItem = [
                'title' => $title,
                'content' => $content,
                'source' => $source['id'],
                'datetime' => $itemDate->format('Y-m-d H:i:s'),
                'uid' => $item->getId(),
                'link' => htmLawed($item->getLink(), ['deny_attribute' => '*', 'elements' => '-*']),
                'author' => $author
            ];

            // save thumbnail
            $newItem['thumbnail'] = $this->fetchThumbnail($item->getThumbnail()) ?: '';

            try {
                // save icon
                $newItem['icon'] = $this->fetchIcon($item->getIcon(), $lasticon) ?: '';
            } catch (\Exception $e) {
                $this->logger->error('icon: error', ['exception' => $e]);
            }

            // insert new item
            $this->itemsDao->add($newItem);
            $this->logger->debug('item inserted');

            $this->logger->debug('Memory usage: ' . memory_get_usage());
            $this->logger->debug('Memory peak usage: ' . memory_get_peak_usage());

            $lastEntry = max($lastEntry, $itemDate->getTimestamp());
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
     * Check if a new item matches the filter
     *
     * @param string $source
     * @param string $title
     * @param string $content
     *
     * @return bool indicating filter success
     */
    protected function filter($source, $title, $content) {
        if (strlen(trim($source['filter'])) !== 0) {
            $resultTitle = @preg_match($source['filter'], $title);
            $resultContent = @preg_match($source['filter'], $content);
            if ($resultTitle === false || $resultContent === false) {
                $this->logger->error('filter error: ' . $source['filter']);

                return true; // do not filter out item
            }
            // test filter
            if ($resultTitle === 0 && $resultContent === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sanitize content for preventing XSS attacks.
     *
     * @param $content content of the given feed
     *
     * @return mixed|string sanitized content
     */
    protected function sanitizeContent($content) {
        return htmLawed(
            $content,
            [
                'safe' => 1,
                'deny_attribute' => '* -alt -title -src -href -target',
                'keep_bad' => 0,
                'comment' => 1,
                'cdata' => 1,
                'elements' => 'div,p,ul,li,a,img,dl,dt,dd,h1,h2,h3,h4,h5,h6,ol,br,table,tr,td,blockquote,pre,ins,del,th,thead,tbody,b,i,strong,em,tt,sub,sup,s,strike,code'
            ],
            'img=width, height'
        );
    }

    /**
     * Sanitize a simple field
     *
     * @param $value content of the given field
     *
     * @return mixed|string sanitized content
     */
    protected function sanitizeField($value) {
        return htmLawed(
            $value,
            [
                'deny_attribute' => '* -href -title -target',
                'elements' => 'a,br,ins,del,b,i,strong,em,tt,sub,sup,s,code'
            ]
        );
    }

    /**
     * Fetch an image URL and process it as a thumbnail.
     *
     * @param string $url the thumbnail URL
     *
     * @return ?string path in the thumbnails directory
     */
    protected function fetchThumbnail($url) {
        if (strlen(trim($url)) > 0) {
            $format = Image::FORMAT_JPEG;
            $extension = Image::getExtension($format);
            $thumbnailAsJpg = $this->imageHelper->loadImage($url, $format, 500, 500);
            if ($thumbnailAsJpg !== null) {
                $written = file_put_contents(
                    \F3::get('datadir') . '/thumbnails/' . md5($url) . '.' . $extension,
                    $thumbnailAsJpg
                );
                if ($written !== false) {
                    $this->logger->debug('Thumbnail generated: ' . $url);

                    return md5($url) . '.' . $extension;
                } else {
                    $this->logger->warning('Unable to store thumbnail: ' . $url . '. Please check permissions of ' . \F3::get('datadir') . '/thumbnails.');
                }
            } else {
                $this->logger->error('thumbnail generation error: ' . $url);
            }
        }

        return null;
    }

    /**
     * Fetch an image and process it as favicon.
     *
     * @param string $url icon given by the spout
     * @param &string $lasticon the last fetched icon
     *
     * @return ?string path in the favicons directory
     */
    protected function fetchIcon($url, &$lasticon) {
        if (strlen(trim($url)) > 0) {
            $format = Image::FORMAT_PNG;
            $extension = Image::getExtension($format);
            if ($url === $lasticon) {
                $this->logger->debug('use last icon: ' . $lasticon);

                return md5($lasticon) . '.' . $extension;
            } else {
                $iconAsPng = $this->imageHelper->loadImage($url, $format, 30, null);
                if ($iconAsPng !== null) {
                    $written = file_put_contents(
                        \F3::get('datadir') . '/favicons/' . md5($url) . '.' . $extension,
                        $iconAsPng
                    );
                    $lasticon = $url;
                    if ($written !== false) {
                        $this->logger->debug('Icon generated: ' . $url);

                        return md5($url) . '.' . $extension;
                    } else {
                        $this->logger->warning('Unable to store icon: ' . $url . '. Please check permissions of ' . \F3::get('datadir') . '/favicons.');
                    }
                } else {
                    $this->logger->error('icon generation error: ' . $url);
                }
            }
        } else {
            $this->logger->debug('no icon for this feed');
        }

        return null;
    }

    /**
     * Obtain title for given data
     *
     * @param $data
     */
    public function fetchTitle($data) {
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
            @error_reporting(E_ERROR);

            $spout->load($data);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching title', ['exception' => $e]);

            return null;
        }

        $title = $spout->getSpoutTitle();
        $spout->destroy();

        return $title;
    }

    /**
     * clean up messages, thumbnails etc.
     *
     * @return void
     */
    public function cleanup() {
        // cleanup orphaned and old items
        $this->logger->debug('cleanup orphaned and old items');
        $this->itemsDao->cleanup((int) \F3::get('items_lifetime'));
        $this->logger->debug('cleanup orphaned and old items finished');

        // delete orphaned thumbnails
        $this->logger->debug('delete orphaned thumbnails');
        $this->cleanupFiles('thumbnails');
        $this->logger->debug('delete orphaned thumbnails finished');

        // delete orphaned icons
        $this->logger->debug('delete orphaned icons');
        $this->cleanupFiles('icons');
        $this->logger->debug('delete orphaned icons finished');

        // optimize database
        $this->logger->debug('optimize database');
        $this->database->optimize();
        $this->logger->debug('optimize database finished');
    }

    /**
     * clean up orphaned thumbnails or icons
     *
     * @param string $type thumbnails or icons
     *
     * @return void
     */
    protected function cleanupFiles($type) {
        if ($type === 'thumbnails') {
            $checker = function($file) {
                return $this->itemsDao->hasThumbnail($file);
            };
            $itemPath = \F3::get('datadir') . '/thumbnails/';
        } elseif ($type === 'icons') {
            $checker = function($file) {
                return $this->itemsDao->hasIcon($file);
            };
            $itemPath = \F3::get('datadir') . '/favicons/';
        }

        foreach (scandir($itemPath) as $file) {
            if (is_file($itemPath . $file) && $file !== '.htaccess') {
                $inUsage = $checker($file);
                if ($inUsage === false) {
                    unlink($itemPath . $file);
                }
            }
        }
    }

    /**
     * Update source (remove previous errors, update last update)
     *
     * @param mixed $source source object
     * @param int $lastEntry timestamp of the newest item or NULL when no items were added
     */
    protected function updateSource($source, $lastEntry) {
        // remove previous error
        if ($source['error'] !== null) {
            $this->sourcesDao->error($source['id'], '');
        }
        // save last update
        $this->sourcesDao->saveLastUpdate($source['id'], $lastEntry);
    }
}
