<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Filters;

use spouts\Item;

/**
 * Filter that admits an item iff the inner filter admits an item obtained from the original item with the given transform function.
 *
 * @template T
 * @template InnerT
 *
 * @implements Filter<T>
 */
final class MapFilter implements Filter {
    /** @var Filter<InnerT> */
    private Filter $filter;

    /** @var callable(T): InnerT */
    private $transform;

    /**
     * @param Filter<InnerT> $filter
     * @param callable(T): InnerT $transform
     */
    public function __construct(Filter $filter, callable $transform) {
        $this->filter = $filter;
        $this->transform = $transform;
    }

    /**
     * @param T $item
     */
    public function admits($item): bool {
        return $this->filter->admits(($this->transform)($item));
    }
}
