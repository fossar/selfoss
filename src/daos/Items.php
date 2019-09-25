<?php

namespace daos;

/**
 * Class for accessing persistent saved items
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Items extends Database {
    /** @var object Instance of backend specific items class */
    private $backend = null;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        $class = 'daos\\' . \F3::get('db_type') . '\\Items';
        $this->backend = new $class();
        parent::__construct();
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

    /**
     * cleanup orphaned and old items
     *
     * @param int $days delete all items older than this value [optional]
     *
     * @return void
     */
    public function cleanup($days) {
        $minDate = null;
        if ($days !== 0) {
            $minDate = new \DateTime();
            $minDate->sub(new \DateInterval('P' . $days . 'D'));
        }
        $this->backend->cleanup($minDate);
    }

    /**
     * returns items
     *
     * @param mixed $options search, offset and filter params
     *
     * @return mixed items as array
     */
    public function get($options = []) {
        $options = array_merge(
            [
                'starred' => false,
                'offset' => 0,
                'search' => false,
                'items' => \F3::get('items_perpage')
            ],
            $options
        );

        $items = $this->backend->get($options);

        // remove private posts with private tags
        if (!\F3::get('auth')->showPrivateTags()) {
            foreach ($items as $idx => $item) {
                foreach ($item['tags'] as $tag) {
                    if (strpos(trim($tag), '@') === 0) {
                        unset($items[$idx]);
                        break;
                    }
                }
            }
            $items = array_values($items);
        }

        // remove posts with hidden tags
        if (!isset($options['tag']) || strlen($options['tag']) === 0) {
            foreach ($items as $idx => $item) {
                foreach ($item['tags'] as $tag) {
                    if (strpos(trim($tag), '#') === 0) {
                        unset($items[$idx]);
                        break;
                    }
                }
            }
            $items = array_values($items);
        }

        return $items;
    }
}
