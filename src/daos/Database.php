<?php

namespace daos;

const PARAM_INT = 1;
const PARAM_BOOL = 2;
const PARAM_CSV = 3;

/**
 * Base class for database access
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Database {
    /** @var DatabaseInterface Instance of backend specific database access class */
    private $backend = null;

    /**
     * establish connection and
     * create undefined tables
     */
    public function __construct(DatabaseInterface $backend) {
        $this->backend = $backend;
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
