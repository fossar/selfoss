<?php

declare(strict_types=1);

namespace helpers;

use helpers\Storage\FileStorage;
use Monolog\Logger;

/**
 * Thumbnail storage.
 */
final class ThumbnailStore {
    public function __construct(
        private Logger $logger,
        private FileStorage $storage
    ) {
    }

    /**
     * Store given blob as URL.
     */
    public function store(string $url, string $blob): ?string {
        $extension = Image::getExtension(ContentLoader::THUMBNAIL_FORMAT);
        $this->logger->debug('Storing thumbnail: ' . $url);

        return $this->storage->store($url, $extension, $blob);
    }

    /**
     * Delete all icons except for requested ones.
     *
     * @param callable(string):bool $shouldKeep
     */
    public function cleanup(callable $shouldKeep): void {
        $this->storage->cleanup($shouldKeep);
    }
}
