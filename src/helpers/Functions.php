<?php

declare(strict_types=1);

namespace helpers\Functions;

use Generator;

/**
 * @template T
 * @template V
 *
 * @param callable(T): V $function
 * @param iterable<T> $collection
 *
 * @return Generator<int, V, void, void>
 */
function map(callable $function, iterable $collection): Generator {
    foreach ($collection as $item) {
        yield $function($item);
    }
}
