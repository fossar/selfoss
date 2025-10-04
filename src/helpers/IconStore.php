<?php

declare(strict_types=1);

namespace Selfoss\helpers;

use Monolog\Logger;
use Selfoss\helpers\Storage\FileStorage;

/**
 * Icon storage.
 */
final readonly class IconStore {
    public function __construct(
        private FileStorage $storage,
        private Logger $logger
    ) {
    }

    /**
     * Store given blob as URL.
     */
    public function store(string $url, string $blob): ?string {
        $extension = Image::getExtension(ContentLoader::ICON_FORMAT);
        $this->logger->debug('Storing icon: ' . $url);

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
