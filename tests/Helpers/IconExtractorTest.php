<?php

declare(strict_types=1);

namespace Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Selfoss\helpers\ImageUtils;

final class IconExtractorTest extends TestCase {
    /**
     * Apple touch icons are supported.
     */
    public function testAppleTouchIcon(): void {
        $page = <<<HTML
            <html>
            <head>
            <link rel="apple-touch-icon" sizes="114x114" href="https://www.example.com/images/apple-touch-114x114.png">
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [
                'https://www.example.com/images/apple-touch-114x114.png',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Apple touch precomposed icons are supported.
     */
    public function testAppleTouchPrecomposedIcon(): void {
        $page = <<<HTML
            <html>
            <head>
            <link rel="apple-touch-icon-precomposed" sizes="114x114" href="https://www.example.com/images/apple-touch-precomposed-114x114.png">
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [
                'https://www.example.com/images/apple-touch-precomposed-114x114.png',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Apple touch icons without sizes are supported.
     */
    public function testAppleTouchWithoutSizesIcon(): void {
        $page = <<<HTML
            <html>
            <head>
            <link rel="apple-touch-icon" href="https://www.example.com/images/apple-touch-114x114.png">
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [
                'https://www.example.com/images/apple-touch-114x114.png',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Shortcut icons are supported.
     */
    public function testShortcutIcon(): void {
        $page = <<<HTML
            <html>
            <head>
            <link rel="shortcut icon" href="https://www.example.com/favicon.ico">
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [
                'https://www.example.com/favicon.ico',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Icons are supported.
     */
    public function testIcon(): void {
        $page = <<<HTML
            <html>
            <head>
            <link rel="icon" href="https://www.example.com/favicon.ico">
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [
                'https://www.example.com/favicon.ico',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Multiple icons are recognized.
     */
    public function testMultipleIcons(): void {
        $page = <<<HTML
            <html>
            <head>
            <link rel="icon" href="/favicon.png" type="image/png">
            <link rel="icon" href="/favicon.ico" type="image/vnd.microsoft.icon">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [
                '/favicon.png',
                '/favicon.ico',
                '/favicon.svg',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Apple icons are prioritized over shortcut icons.
     */
    public function testAppleAndIcon(): void {
        $page = <<<HTML
            <html>
            <head>
            <link rel="icon" href="/favicon.ico">
            <link rel="apple-touch-icon" sizes="114x114" href="https://www.example.com/images/apple-touch-114x114.png">
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [
                'https://www.example.com/images/apple-touch-114x114.png',
                '/favicon.ico',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Larger icons are prioritized over smaller ones.
     */
    public function testAppleAndPrecomposed(): void {
        $page = <<<HTML
            <html>
            <head>
            <link rel="apple-touch-icon" sizes="144x144" href="https://www.example.com/images/apple-touch-114x114.png">
            <link rel="apple-touch-icon-precomposed" sizes="87x87" href="https://www.example.com/images/apple-touch-precomposed-87x87.png">
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [
                'https://www.example.com/images/apple-touch-114x114.png',
                'https://www.example.com/images/apple-touch-precomposed-87x87.png',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Apple precomposed icons are prioritized over normal touch icons.
     */
    public function testAppleAndPrecomposedWithoutSizes(): void {
        $page = <<<HTML
            <html>
            <head>
            <link rel="apple-touch-icon" href="https://www.example.com/images/apple-touch-114x114.png">
            <link rel="apple-touch-icon-precomposed" href="https://www.example.com/images/apple-touch-precomposed-114x114.png">
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [
                'https://www.example.com/images/apple-touch-precomposed-114x114.png',
                'https://www.example.com/images/apple-touch-114x114.png',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Uglified websites with unquoted atttributes are handled correctly.
     */
    public function testUnquotedAttributes(): void {
        $page = <<<HTML
            <html><head><link rel="shortcut icon" href=//www.example.com/favicons/favicon.ico><link rel=apple-touch-icon sizes=152x152 href=//www.example.com/favicons/apple-touch-icon-152x152.png><link rel=icon type=image/png href=//www.example.com/favicons/favicon-196x196.png sizes=196x196></head><body></body></html>
            HTML;

        $this->assertEquals(
            [
                '//www.example.com/favicons/favicon-196x196.png',
                '//www.example.com/favicons/apple-touch-icon-152x152.png',
                '//www.example.com/favicons/favicon.ico',
            ],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Null is returned when no icon found.
     */
    public function testMissingIcon(): void {
        $page = <<<HTML
            <html>
            <head>
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Commented out icons are ignored.
     */
    public function testCommentIcon(): void {
        $page = <<<HTML
            <html>
            <head>
            <!--<link rel="icon" href="https://www.example.com/favicon.ico">-->
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [],
            ImageUtils::parseShortcutIcons($page)
        );
    }

    /**
     * Icons inside script elements are ignored.
     */
    public function testScriptIcon(): void {
        $page = <<<HTML
            <html>
            <head>
            <script>
            console.log('<link rel="icon" href="https://www.example.com/favicon.ico">');
            </script>
            </head>
            <body>
            </body>
            </html>
            HTML;

        $this->assertEquals(
            [],
            ImageUtils::parseShortcutIcons($page)
        );
    }
}
