<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Filters;

use spouts\Item;

/**
 * Filter that always admits an item.
 *
 * @implements Filter<mixed>
 */
final class AcceptingFilter implements Filter {
    /**
     * @param mixed $item
     */
    public function admits($item): bool {
        return true;
    }
}
