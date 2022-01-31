<?php

namespace helpers;

/**
 * Allow iterating spout’s items directly through the spout object.
 */
trait ItemsIterator {
    /** @var ?\Iterator current fetched items */
    protected $items = null;

    #[\ReturnTypeWillChange]
    public function rewind() {
        if ($this->items !== null) {
            $this->items->rewind();
        }
    }

    /**
     * receive current item
     *
     * @return self|null current item
     */
    #[\ReturnTypeWillChange]
    public function current() {
        if ($this->items !== null) {
            return $this;
        }

        return null;
    }

    /**
     * receive key of current item
     *
     * @return mixed|null key of current item
     */
    #[\ReturnTypeWillChange]
    public function key() {
        if ($this->items !== null) {
            return $this->items->key();
        }

        return null;
    }

    /**
     * select next item
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next() {
        if ($this->items !== null) {
            $this->items->next();
        }
    }

    /**
     * end reached
     *
     * @return bool false if end reached
     */
    #[\ReturnTypeWillChange]
    public function valid() {
        if ($this->items !== null) {
            return $this->items->valid();
        }

        return false;
    }
}
