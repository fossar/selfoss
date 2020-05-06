<?php

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

    /**
     * @param string $directory
     */
    public function __construct(Logger $logger, $directory) {
        $this->logger = $logger;
        $this->directory = $directory;
    }

    /**
     * Store given blob with type $extension as URL.
     *
     * @param string $url
     * @param string $extension
     * @param string $blob
     *
     * @return ?string
     */
    public function store($url, $extension, $blob) {
        $filename = md5($url) . '.' . $extension;
        $path = $this->directory . '/' . $filename;
        $written = file_put_contents($path, $blob);

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
     *
     * @return ?string
     */
    public function cleanup($shouldKeep) {
        $itemPath = $this->directory . '/';
        foreach (scandir($itemPath) as $file) {
            if (is_file($itemPath . $file) && $file !== '.htaccess') {
                if (!$shouldKeep($file)) {
                    unlink($itemPath . $file);
                }
            }
        }
    }
}
