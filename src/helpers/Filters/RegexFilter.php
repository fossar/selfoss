<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Selfoss\helpers\Filters;

/**
 * Filter that admits an item iff the given regular expression matches it.
 *
 * @implements Filter<string>
 */
final readonly class RegexFilter implements Filter {
    private string $regex;

    public function __construct(string $regex) {
        if (@preg_match('/^\\/((?<!\\\\)(?:\\\\)*\\/|[^\\/])*\\/$/', $regex) === 0) {
            throw new FilterSyntaxError("Invalid regex {$regex}, should start and end with a forward slash and not contain un-escaped forward slashes");
        }

        if (@preg_match($regex, '') === false) {
            throw new FilterSyntaxError("Invalid regex {$regex}");
        }

        $this->regex = $regex;
    }

    /**
     * @param string $item
     */
    public function admits($item): bool {
        $result = @preg_match($this->regex, $item);
        \assert($result !== false); // Verified at construction.

        return $result === 1;
    }
}
