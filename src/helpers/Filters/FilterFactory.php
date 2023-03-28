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

        if (@preg_match('/^(?P<negated>!)?(?:(?P<field>[^:]*):)?(?P<regex>.+)$/', $expression, $match) === 0) {
            throw new FilterSyntaxError("Invalid filter expression {$expression}, see https://selfoss.aditu.de/docs/usage/filters/");
        }

        $filter = new RegexFilter($match['regex']);
        $field = $match['field'];

        if ($field === '') {
            $filter = new MapFilter(new DisjunctionFilter($filter), Closure::fromCallable([self::class, 'getTitleAndContentStrings']));
        } elseif ($field === 'title') {
            $filter = new MapFilter($filter, Closure::fromCallable([self::class, 'getTitleString']));
        } elseif ($field === 'content') {
            $filter = new MapFilter($filter, Closure::fromCallable([self::class, 'getContentString']));
        } else {
            throw new FilterSyntaxError("Invalid filter expression {$expression}, field must be one of “title” or “content”.");
        }

        if ($match['negated'] === '!') {
            $filter = new NegationFilter($filter);
        }

        return $filter;
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
