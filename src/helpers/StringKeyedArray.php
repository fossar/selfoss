<?php

// SPDX-FileCopyrightText: 2016–2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Selfoss\helpers;

use ArrayAccess;
use Generator;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;

/**
 * PHP will turn `string` array keys to `int` when they look like an `int`.
 * This can result in unexpected runtime errors with `strict_types`.
 *
 * @template T
 *
 * @implements ArrayAccess<string, T>
 * @implements IteratorAggregate<string, T>
 */
final class StringKeyedArray implements ArrayAccess, IteratorAggregate, JsonSerializable {
    /**
     * @param array<string|int, T> $data
     */
    public function __construct(
        /** @var array<string|int, T> */
        private array $data = []
    ) {
    }

    /**
     * @param string $offset
     */
    public function offsetExists($offset): bool {
        return array_key_exists($offset, $this->data);
    }

    /**
     * @param string $offset
     *
     * @throws OutOfBoundsException when given offset does not exist
     *
     * @return T
     */
    #[\ReturnTypeWillChange]
    public function &offsetGet($offset) {
        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundsException("Undefined array key “{$offset}”");
        }

        return $this->data[$offset];
    }

    /**
     * @param string $offset
     * @param T $value
     */
    public function offsetSet($offset, $value): void {
        $this->data[$offset] = $value;
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset): void {
        unset($this->data[$offset]);
    }

    /**
     * @return Generator<string, T>
     */
    public function getIterator(): Generator {
        return (function() {
            foreach ($this->data as $key => $value) {
                yield ((string) $key) => $value;
            }
        })();
    }

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize() {
        // Objects can only have strings for keys.
        return (object) $this->data;
    }
}
