<?php

namespace helpers;

use Dice\Dice;
use spouts\spout;

/**
 * Helper class for loading spouts (special spouts which
 * defines an spout for this application)
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class SpoutLoader {
    /** @var ?array<class-string<spout>, spout> array of available spouts */
    private $spouts = null;

    /** @var Dice dependency injection container */
    private $dic;

    public function __construct(Dice $dice) {
        $this->dic = $dice;
    }

    /**
     * returns all available spouts
     *
     * @return array<class-string<spout>, spout> available spouts
     */
    public function all() {
        $this->readSpouts();

        return $this->spouts;
    }

    /**
     * returns a given spout object
     *
     * @param string $spout a given spout type
     *
     * @return ?spout an instance of the spout, null if this spout doesn't exist
     */
    public function get($spout) {
        if (!class_exists($spout)) {
            return null;
        }

        try {
            $class = $this->dic->create($spout);

            if (is_subclass_of($class, spout::class)) {
                return $class;
            } else {
                return null;
            }
        } catch (\ReflectionException $e) {
            return null;
        }
    }

    //
    // private helpers
    //

    /**
     * reads all spouts
     *
     * @return void
     */
    protected function readSpouts() {
        if ($this->spouts === null) {
            $this->spouts = $this->loadClasses(__DIR__ . '/../spouts', spout::class);

            // sort spouts by name
            uasort($this->spouts, ['self', 'compareSpoutsByName']);
        }
    }

    /**
     * returns all classes which extends a given class
     *
     * @template P
     *
     * @param string $location the path where all spouts in
     * @param class-string<P> $parentClassName the parent class which files must extend
     *
     * @return array<class-string<P>, P> list of instantiated spouts associated to their class names
     */
    protected function loadClasses($location, $parentClassName) {
        $return = [];

        foreach (scandir($location) as $dir) {
            if (is_dir($location . '/' . $dir) && substr($dir, 0, 1) !== '.') {
                // search for spouts
                foreach (scandir($location . '/' . $dir) as $file) {
                    // only scan visible .php files
                    if (is_file($location . '/' . $dir . '/' . $file) && substr($file, 0, 1) !== '.' && strpos($file, '.php') !== false) {
                        // create reflection class
                        /** @var class-string<P> */
                        $className = 'spouts\\' . $dir . '\\' . str_replace('.php', '', $file);

                        // register widget
                        if (is_subclass_of($className, $parentClassName)) {
                            /** @var P */
                            $class = $this->dic->create($className);
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
     * @param spout $spout1 Spout 1
     * @param spout $spout2 Spout 2
     *
     * @return int
     */
    private static function compareSpoutsByName(spout $spout1, spout $spout2) {
        return strcasecmp($spout1->name, $spout2->name);
    }
}
