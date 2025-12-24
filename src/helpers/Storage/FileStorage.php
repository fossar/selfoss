<?php

declare(strict_types=1);

namespace helpers\Storage;

use Monolog\Logger;

/**
 * Simple file storage.
 */
final class FileStorage {
    public function __construct(
        private Logger $logger,
        /** Directory where the files will be stored */
        private string $directory
    ) {
    }

    /**
     * Store given blob with type $extension as URL.
     */
    public function store(string $url, string $extension, string $blob): ?string {
        $filename = md5($url) . '.' . $extension;
        $path = $this->directory . '/' . $filename;
        $written = @file_put_contents($path, $blob);

        if ($written !== false) {
            return $filename;
        } else {
            $this->logger->warning('Unable to store file: ' . $url . '. Please check permissions of ' . $this->directory);

            return null;
        }
    }

    /**
     * Delete all files except for requested ones.
     *
     * @param callable(string):bool $shouldKeep
     */
    public function cleanup(callable $shouldKeep): void {
        $undeleted = [];
        foreach (new \DirectoryIterator($this->directory) as $fileInfo) {
            $name = $fileInfo->getFilename();
            if ($fileInfo->isFile() && $name !== '.htaccess') {
                if (!$shouldKeep($name)) {
                    $path = $fileInfo->getPathname();
                    if (!@unlink($path)) {
                        $undeleted[] = $path;
                    }
                }
            }
        }

        if (count($undeleted) > 0) {
            $this->logger->warning('Unable to delete file: ' . $undeleted[0] . '. Please check permissions of ' . $this->directory);
        }
    }
}
