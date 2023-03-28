<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Tests\Helpers;

use DateTimeImmutable;
use helpers\Filters\Filter;
use helpers\Filters\FilterFactory;
use helpers\Filters\FilterSyntaxError;
use helpers\Filters\MapFilter;
use helpers\Filters\RegexFilter;
use helpers\HtmlString;
use PHPUnit\Framework\TestCase;
use spouts\Item;

final class FilterTest extends TestCase {
    /**
     * @return iterable<array{string}>
     */
    public function invalidRegexProvider(): iterable {
        yield 'No slashes' => [
            'pattern',
        ];

        yield 'Unescaped slash within' => [
            '/pat/tern/',
        ];

        yield 'Modifiers' => [
            '/pattern/i',
        ];

        yield 'Unsupported delimiters' => [
            '(pattern)',
        ];

        yield 'Empty string' => [
            '',
        ];
    }

    /**
     * @dataProvider invalidRegexProvider
     */
    public function testRegexError(string $regex): void {
        $this->expectException(FilterSyntaxError::class);
        new RegexFilter($regex);
    }

    /**
     * @return iterable<array{string, class-string<Filter<mixed>>}>
     */
    public function validPatternProvider(): iterable {
        yield 'Plain' => [
            '/pattern/',
            MapFilter::class,
        ];

        yield 'Escaped slash within' => [
            '/pat\\/tern/',
            MapFilter::class,
        ];

        yield 'Modifiers' => [
            '/(?i)pattern/',
            MapFilter::class,
        ];
    }

    /**
     * @param class-string<Filter<mixed>> $class
     *
     * @dataProvider validPatternProvider
     */
    public function testRegexOkay(string $expression, string $class): void {
        $filter = FilterFactory::fromString($expression);
        $this->assertInstanceOf($class, $filter);
    }

    /**
     * @return Item<mixed>
     */
    private static function mkItem(string $title, string $content, ?string $author = null): Item {
        return new Item(
            /* id: */ '0',
            /* title: */ HtmlString::fromRaw($title),
            /* content: */ HtmlString::fromRaw($content),
            /* thumbnail: */ null,
            /* icon: */ null,
            /* link: */ '',
            /* date: */ new DateTimeImmutable(),
            /* author: */ $author,
            /* extraData: */ null
        );
    }

    /**
     * @return iterable<array{string, Item<mixed>, bool}>
     */
    public function admittanceProvider(): iterable {
        yield 'Item: No match' => [
            '/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'foo'
            ),
            false,
        ];

        yield 'Item: Title match' => [
            '/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'foo'
            ),
            true,
        ];

        yield 'Item: Content match' => [
            '/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'Regular expressions are great.'
            ),
            true,
        ];

        yield 'Item: Both match' => [
            '/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'Regular expressions are great.'
            ),
            true,
        ];

        yield 'Not(Item): No match' => [
            '!/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'foo'
            ),
            true,
        ];

        yield 'Not(Item): Title match' => [
            '!/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'foo'
            ),
            false,
        ];

        yield 'Not(Item): Content match' => [
            '!/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'Regular expressions are great.'
            ),
            false,
        ];

        yield 'Not(Item): Both match' => [
            '!/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'Regular expressions are great.'
            ),
            false,
        ];

        yield 'Title: No match' => [
            'title:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'foo'
            ),
            false,
        ];

        yield 'Title: Title match' => [
            'title:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'foo'
            ),
            true,
        ];

        yield 'Title: Content match' => [
            'title:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'Regular expressions are great.'
            ),
            false,
        ];

        yield 'Title: Both match' => [
            'title:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'Regular expressions are great.'
            ),
            true,
        ];

        yield 'Not(Title): No match' => [
            '!title:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'foo'
            ),
            true,
        ];

        yield 'Not(Title): Title match' => [
            '!title:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'foo'
            ),
            false,
        ];

        yield 'Not(Title): Content match' => [
            '!title:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'Regular expressions are great.'
            ),
            true,
        ];

        yield 'Not(Title): Both match' => [
            '!title:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'Regular expressions are great.'
            ),
            false,
        ];

        yield 'Content: No match' => [
            'content:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'foo'
            ),
            false,
        ];

        yield 'Content: Title match' => [
            'content:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'foo'
            ),
            false,
        ];

        yield 'Content: Content match' => [
            'content:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'Regular expressions are great.'
            ),
            true,
        ];

        yield 'Content: Both match' => [
            'content:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'Regular expressions are great.'
            ),
            true,
        ];

        yield 'Not(Content): No match' => [
            '!content:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'foo'
            ),
            true,
        ];

        yield 'Not(Content): Title match' => [
            '!content:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'foo'
            ),
            true,
        ];

        yield 'Not(Content): Content match' => [
            '!content:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'Regular expressions are great.'
            ),
            false,
        ];

        yield 'Not(Content): Both match' => [
            '!content:/(?i)reg(ular expression|exp)/',
            self::mkItem(
                /* title: */ 'Regexp tips and tricks',
                /* content: */ 'Regular expressions are great.'
            ),
            false,
        ];

        yield 'Author: Match' => [
            'author:/John/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'foo',
                /* author: */ 'John'
            ),
            true,
        ];

        yield 'Author: No match' => [
            'author:/John/',
            self::mkItem(
                /* title: */ 'John’s risotto recipe',
                /* content: */ 'John recommends using rice.',
                /* author: */ 'Josh'
            ),
            false,
        ];

        yield 'Author: No author' => [
            'author:/John/',
            self::mkItem(
                /* title: */ 'John’s risotto recipe',
                /* content: */ 'John recommends using rice.'
            ),
            false,
        ];

        yield 'Not(Author): Match' => [
            '!author:/John/',
            self::mkItem(
                /* title: */ 'foo',
                /* content: */ 'foo',
                /* author: */ 'John'
            ),
            false,
        ];

        yield 'Not(Author): No match' => [
            '!author:/John/',
            self::mkItem(
                /* title: */ 'John’s risotto recipe',
                /* content: */ 'John recommends using rice.',
                /* author: */ 'Josh'
            ),
            true,
        ];

        yield 'Not(Author): No author' => [
            '!author:/John/',
            self::mkItem(
                /* title: */ 'John’s risotto recipe',
                /* content: */ 'John recommends using rice.'
            ),
            true,
        ];
    }

    /**
     * @param Item<mixed> $item
     *
     * @dataProvider admittanceProvider
     */
    public function testAdmittance(string $expression, Item $item, bool $admitted): void {
        $filter = FilterFactory::fromString($expression);
        $this->assertSame($admitted, $filter->admits($item));
    }
}
