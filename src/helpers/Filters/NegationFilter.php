<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Filters;

use spouts\Item;

/**
 * Filter that rejects an item iff inner filter admits it.
 *
 * @template T
 *
 * @implements Filter<T>
 */
final class NegationFilter implements Filter {
    /** @var Filter<T> */
    private $filter;

    /**
     * @param Filter<T> $filter
     */
    public function __construct(Filter $filter) {
        $this->filter = $filter;
    }

    /**
     * @param T $item
     */
    public function admits($item): bool {
        return !$this->filter->admits($item);
    }
}
