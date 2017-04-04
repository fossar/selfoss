<?php

namespace daos;

/**
 * Class for accessing tag colors
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Tags extends Database {
    /** @var object Instance of backend specific sources class */
    private $backend = null;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        $class = 'daos\\' . \F3::get('db_type') . '\\Tags';
        $this->backend = new $class();
        parent::__construct();
    }

    public function get() {
        $tags = $this->backend->get();
        // remove items with private tags
        if (!\F3::get('auth')->showPrivateTags()) {
            foreach ($tags as $idx => $tag) {
                if (strpos($tag['tag'], '@') === 0) {
                    unset($tags[$idx]);
                }
            }
            $tags = array_values($tags);
        }

        return $tags;
    }

    /**
     * pass any method call to the backend.
     *
     * @param string $name name of the function
     * @param array $args arguments
     *
     * @return mixed methods return value
     */
    public function __call($name, $args) {
        if (method_exists($this->backend, $name)) {
            return call_user_func_array([$this->backend, $name], $args);
        } else {
            \F3::get('logger')->error('Unimplemented method for ' . \F3::get('db_type') . ': ' . $name);
        }
    }
}
