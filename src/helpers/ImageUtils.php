<?php

declare(strict_types=1);

namespace helpers;

/**
 * Helper class for loading images
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class ImageUtils {
    private const ICON_REL_WEIGHTS = [
        'apple-touch-icon-precomposed' => 3,
        'apple-touch-icon' => 2,
        'shortcut icon' => 1,
        'icon' => 1,
    ];

    /**
     * removes $startString, $endString and everything in between from $subject
     */
    public static function cleanString(string $startString, string $endString, string $subject): string {
        while (false !== $p1 = stripos($subject, $startString)) {
            $block = substr($subject, $p1);
            $subject = substr($subject, 0, $p1);
            if (false !== $p2 = stripos($block, $endString)) {
                $subject .= substr($block, $p2 + strlen($endString));
            }
        }

        return $subject;
    }

    /**
     * parse shortcut icons from given html
     * If icons are found, their URLs are returned in an array ordered by size
     * if given or by likely size of not, with the biggest one first.
     *
     * @return string[]
     */
    public static function parseShortcutIcons(string $html): array {
        $end = strripos($html, '</head>');
        if ($end > 1) {
            $html = substr($html, 0, $end);
        }

        // $html= preg_replace('/<!--.*-->/sU', '', preg_replace('#<(script|style)\b[^>]*>.*</\1>#sU', '', $html));
        // should to the same, but doesn't always.
        $html = self::cleanString('<script', '</script>', $html);
        $html = self::cleanString('<style', '</style>', $html);
        $html = self::cleanString('<!--', '-->', $html);

        // This is only very rough approximation of HTML tag as described by
        // https://www.w3.org/TR/2012/WD-html-markup-20120329/syntax.html#syntax-start-tags
        // Ideally, we would use a HTML parser but even the streaming parsers I tried
        // were several orders of magnitude slower than this mess.
        if (preg_match_all('#<link\b[^>]*\srel=("|\'|)(?P<rel>apple-touch-icon|apple-touch-icon-precomposed|shortcut icon|icon)\1[^>]*>#iU', $html, $links, PREG_SET_ORDER) < 1) {
            return [];
        }

        $icons = [];
        $i = 0;
        foreach ($links as $link) {
            if (preg_match('#\shref=(?:("|\')(?P<url>.+)\1|(?P<uq_url>[^\s"\'=><`]+?))#iU', $link[0], $href, PREG_UNMATCHED_AS_NULL)) {
                // For PHPStan: The capture groups are all the alternatives so it cannot be null.
                /** @var non-empty-string */
                $url = $href['uq_url'] ?? $href['url'];
                $icons[] = [
                    'url' => $url,
                    // Sizes are only used by Apple.
                    // https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariWebContent/ConfiguringWebApplications/ConfiguringWebApplications.html
                    'sizes' => preg_match('#\ssizes=("|\'|)(?P<sizes>[0-9\.]+)x\2\1#i', $link[0], $sizes)
                        ? $sizes['sizes']
                        : 0,
                    'rel' => $link['rel'],
                    'order' => $i++,
                ];
            }
        }
        if (count($icons) === 0) {
            return $icons;
        }

        usort($icons, Misc::compareBy(
            // largest icons first
            [
                fn($val) => (int) $val['sizes'],
                Misc::ORDER_DESC,
            ],

            // then by rel priority
            [
                fn($val) => self::ICON_REL_WEIGHTS[$val['rel']],
                Misc::ORDER_DESC,
            ],

            // and finally by order to make the sorting stable
            'order'
        ));

        return array_map(
            fn($i) => $i['url'],
            $icons
        );
    }

    /**
     * taken from: http://zytzagoo.net/blog/2008/01/23/extracting-images-from-html-using-regular-expressions/
     * Searches for the first occurence of an html <img> element in a string
     * and extracts the src if it finds it. Returns null in case an <img>
     * element is not found.
     *
     * @param string $html An HTML string
     *
     * @return ?string content of the src attribute of the first image
     */
    public static function findFirstImageSource(string $html): ?string {
        if (stripos($html, '<img') !== false) {
            $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
            if (preg_match($imgsrc_regex, $html, $matches)) {
                return htmlspecialchars_decode($matches[2]);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Detect if given data is an SVG file and not just a HTML document with SVG embedded.
     *
     * @return bool true when it is a SVG
     */
    public static function detectSvg(string $blob): bool {
        return preg_match('#<svg[\s>]#si', $blob) && !preg_match('#<((!doctype )?html|body)[\s>]#si', $blob);
    }
}
