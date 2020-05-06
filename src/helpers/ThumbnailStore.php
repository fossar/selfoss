<?php

namespace helpers;

use helpers\Storage\FileStorage;
use Monolog\Logger;

/**
 * Thumbnail storage.
 */
class ThumbnailStore {
    /** @var Logger */
    private $logger;

    /** @var FileStorage */
    private $storage;

    public function __construct(Logger $logger, FileStorage $storage) {
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * Store given blob as URL.
     *
     * @param string $url
     * @param string $blob
     *
     * @return ?string
     */
    public function store($url, $blob) {
        $extension = Image::getExtension(ContentLoader::THUMBNAIL_FORMAT);
        $this->logger->debug('Storing thumbnail: ' . $url);

        return $this->storage->store($url, $extension, $blob);
    }

    /**
     * Delete all icons except for requested ones.
     *
     * @param callable(string):bool $shouldKeep
     *
     * @return void
     */
    public function cleanup($shouldKeep) {
        $this->storage->cleanup($shouldKeep);
    }
}
