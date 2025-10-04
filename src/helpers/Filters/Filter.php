<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Selfoss\helpers\Filters;

use spouts\Item;

/**
 * Represents a predicate function.
 *
 * Mainly meant to be used for removing items from sources,
 * when the template parameter is instantiated to `Item`.
 *
 * @template T
 */
interface Filter {
    /**
     * Checks if a value matches the filter.
     *
     * @param T $item
     *
     * @return bool indicating filter success
     */
    public function admits($item): bool;
}
