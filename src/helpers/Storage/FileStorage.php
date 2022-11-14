<?php

declare(strict_types=1);

namespace helpers\Storage;

use Monolog\Logger;

/**
 * Simple file storage.
 */
class FileStorage {
    /** @var Logger */
    private $logger;

    /** @var string Directory where the files will be stored */
    private $directory;

    public function __construct(Logger $logger, string $directory) {
        $this->logger = $logger;
        $this->directory = $directory;
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
        $itemPath = $this->directory . '/';
        $undeleted = [];
        foreach (scandir($itemPath) as $file) {
            if (is_file($itemPath . $file) && $file !== '.htaccess') {
                if (!$shouldKeep($file)) {
                    if (!@unlink($itemPath . $file)) {
                        $undeleted[] = $itemPath . $file;
                    }
                }
            }
        }

        if (count($undeleted) > 0) {
            $this->logger->warning('Unable to delete file: ' . $undeleted[0] . '. Please check permissions of ' . $this->directory);
        }
    }
}
