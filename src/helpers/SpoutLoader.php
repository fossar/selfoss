<?php

declare(strict_types=1);

namespace helpers;

use Psr\Container\ContainerInterface;
use spouts\spout;

/**
 * Helper class for loading spouts (special spouts which
 * defines an spout for this application)
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
final class SpoutLoader {
    /** @var ?array<class-string<spout<mixed>>, spout<mixed>> array of available spouts */
    private ?array $spouts = null;

    public function __construct(
        private ContainerInterface $container
    ) {
    }

    /**
     * returns all available spouts
     *
     * @return array<class-string<spout<mixed>>, spout<mixed>> available spouts
     */
    public function all(): array {
        if ($this->spouts === null) {
            $this->spouts = $this->readSpouts();
        }

        return $this->spouts;
    }

    /**
     * returns a given spout object
     *
     * @param string $spout a given spout type
     *
     * @return ?spout<mixed> an instance of the spout, null if this spout doesn't exist
     */
    public function get(string $spout): ?spout {
        if (!class_exists($spout)) {
            return null;
        }

        try {
            $class = $this->container->get($spout);

            if ($class instanceof spout) {
                return $class;
            } else {
                return null;
            }
        } catch (\ReflectionException) {
            return null;
        }
    }

    //
    // private helpers
    //

    /**
     * reads all spouts
     *
     * @return array<class-string<spout<mixed>>, spout<mixed>>
     */
    private function readSpouts(): array {
        $spouts = $this->loadClasses(__DIR__ . '/../spouts', spout::class);

        // sort spouts by name
        uasort($spouts, [self::class, 'compareSpoutsByName']);

        return $spouts;
    }

    /**
     * returns all classes which extends a given class
     *
     * @template P of spout
     *
     * @param string $location the path where all spouts in
     * @param class-string<P> $parentClassName the parent class which files must extend
     *
     * @return array<class-string<P>, P> list of instantiated spouts associated to their class names
     */
    protected function loadClasses(string $location, string $parentClassName): array {
        $return = [];

        foreach (new \DirectoryIterator($location) as $dirInfo) {
            if ($dirInfo->isDir() && !$dirInfo->isDot()) {
                // search for spouts
                foreach (new \DirectoryIterator($dirInfo->getPathname()) as $fileInfo) {
                    $name = $fileInfo->getFilename();
                    // only scan visible .php files
                    if ($fileInfo->isFile() && !str_starts_with($name, '.') && $fileInfo->getExtension() === 'php') {
                        // create reflection class
                        /** @var class-string<P> */
                        $className = 'spouts\\' . $dirInfo->getFilename() . '\\' . $fileInfo->getBasename('.php');

                        // register widget
                        if (is_subclass_of($className, $parentClassName)) {
                            /** @var P */
                            $class = $this->container->get($className);
                            $return[$className] = $class;
                        }
                    }
                }
            }
        }

        return $return;
    }

    /**
     * compare spouts by name
     *
     * @param spout<mixed> $spout1 Spout 1
     * @param spout<mixed> $spout2 Spout 2
     */
    private static function compareSpoutsByName(spout $spout1, spout $spout2): int {
        return strcasecmp($spout1->name, $spout2->name);
    }
}
