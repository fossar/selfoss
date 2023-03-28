<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Filters;

use Closure;
use spouts\Item;

final class FilterFactory {
    /**
     * Creates a filter based on filter expression language.
     * See [filter docs](https://selfoss.aditu.de/docs/usage/filters/).
     *
     * @throws FilterSyntaxError when the expression is not valid
     *
     * @return Filter<Item<mixed>>
     */
    public static function fromString(string $expression): Filter {
        if ($expression === '') {
            return new AcceptingFilter();
        }

        $filter = new RegexFilter($expression);

        return new MapFilter(new DisjunctionFilter($filter), Closure::fromCallable([self::class, 'getTitleAndContentStrings']));
    }

    /**
     * @param Item<mixed> $item
     *
     * @return array{string, string}
     */
    private static function getTitleAndContentStrings(Item $item): array {
        return [
            self::getTitleString($item),
            self::getContentString($item),
        ];
    }

    /**
     * @param Item<mixed> $item
     */
    private static function getTitleString(Item $item): string {
        return $item->getTitle()->getRaw();
    }

    /**
     * @param Item<mixed> $item
     */
    private static function getContentString(Item $item): string {
        return $item->getContent()->getRaw();
    }
}
