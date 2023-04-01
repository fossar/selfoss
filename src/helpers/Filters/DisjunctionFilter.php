<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Filters;

/**
 * Filter that admits a list of items iff the inner filter admits any of them.
 *
 * @template T
 *
 * @implements Filter<array<T>>
 */
final class DisjunctionFilter implements Filter {
    /** @var Filter<T> */
    private $filter;

    /**
     * @param Filter<T> $filter
     */
    public function __construct($filter) {
        $this->filter = $filter;
    }

    /**
     * @param array<T> $items
     */
    public function admits($items): bool {
        foreach ($items as $item) {
            if ($this->filter->admits($item)) {
                return true;
            }
        }

        return false;
    }
}
