<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Selfoss\helpers\Filters;

/**
 * Filter that rejects an item iff inner filter admits it.
 *
 * @template T
 *
 * @implements Filter<T>
 */
final readonly class NegationFilter implements Filter {
    /**
     * @param Filter<T> $filter
     */
    public function __construct(
        /** @var Filter<T> */
        private Filter $filter
    ) {
    }

    /**
     * @param T $item
     */
    public function admits($item): bool {
        return !$this->filter->admits($item);
    }
}
