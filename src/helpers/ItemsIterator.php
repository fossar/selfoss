<?php

namespace helpers;

/**
 * Allow iterating spout’s items directly through the spout object.
 */
trait ItemsIterator {
    /** @var ?array current fetched items */
    protected $items = null;

    public function rewind() {
        if ($this->items !== null) {
            reset($this->items);
        }
    }

    /**
     * receive current item
     *
     * @return self|false current item
     */
    public function current() {
        if ($this->items !== null) {
            return $this;
        }

        return false;
    }

    /**
     * receive key of current item
     *
     * @return mixed key of current item
     */
    public function key() {
        if ($this->items !== null) {
            return key($this->items);
        }

        return false;
    }

    /**
     * select next item
     *
     * @return self next item
     */
    public function next() {
        if ($this->items !== null) {
            next($this->items);
        }

        return $this;
    }

    /**
     * end reached
     *
     * @return bool false if end reached
     */
    public function valid() {
        if ($this->items !== null) {
            return current($this->items) !== false;
        }

        return false;
    }
}
