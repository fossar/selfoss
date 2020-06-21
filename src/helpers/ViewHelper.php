<?php

namespace helpers;

/**
 * Helper class for loading extern items
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class ViewHelper {
    /**
     * Enclose all searchWords with <span class="found">$word</span>
     * for later highlighing with CSS
     *
     * @param string $content which contains words
     * @param array|string $searchWords words for highlighting
     *
     * @return string with highlited words
     */
    public static function highlight($content, $searchWords) {
        if (strlen(trim($searchWords)) === 0) {
            return $content;
        }

        if (!is_array($searchWords)) {
            $searchWords = \helpers\Search::splitTerms($searchWords);
        }

        foreach ($searchWords as $word) {
            $content = preg_replace('/(?!<[^<>])(' . $word . ')(?![^<>]*>)/i', '<span class="found">$0</span>', $content);
        }

        return $content;
    }

    /**
     * removes img src attribute and saves the value in data attribute for
     * loading it later
     *
     * @param string $content which contains img tags
     *
     * @return string with replaced img tags
     */
    public static function lazyimg($content) {
        return preg_replace_callback("/<img(?P<pre>[^<]+)src=(?:['\"])(?P<src>[^\"']*)(?:['\"])(?P<post>[^<]*)>/i", function($matches) {
            $width = null;
            $height = null;

            $attrs = "{$matches['pre']} {$matches['post']}";
            if (preg_match('/\bwidth=([\'"]?)(?P<width>\d+)\1/i', $attrs, $widthAttr)) {
                $width = $widthAttr['width'];
            }
            if (preg_match('/\bheight=([\'"]?)(?P<height>\d+)\1/i', $attrs, $heightAttr)) {
                $height = $heightAttr['height'];
            }

            if ($width === null && $height === null) {
                // no dimensions provided, assume a 4:3 photo
                $width = 800;
                $height = 600;
            } else {
                // assume a 4:3 photo
                $width = $width === null ? $height * 4 / 3 : $width;
                $height = $height === null ? $width * 3 / 4 : $height;
            }

            $placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='$width' height='$height'><rect fill='%2395c9c5' width='100%' height='100%'/></svg>";

            return "<img src=\"$placeholder\"{$matches['pre']}data-selfoss-src=\"{$matches['src']}\"{$matches['post']}>";
        }, $content);
    }

    /**
     * Return ISO8601 formatted date
     *
     * @param string $datestr sql date
     *
     * @return string
     */
    public static function date_iso8601($datestr) {
        $date = new \DateTime($datestr);

        return $date->format(\DateTime::ATOM);
    }

    /**
     * Proxify imgs through atmos/camo when not https
     *
     * @param  string $content item content
     *
     * @return string          item content
     */
    public static function camoflauge($content) {
        if (empty($content)) {
            return $content;
        }

        $camo = new \WillWashburn\Phpamo\Phpamo(\F3::get('camo_key'), \F3::get('camo_domain'));

        return preg_replace_callback("/<img([^<]+)src=(['\"])([^\"']*)(['\"])([^<]*)>/i", function($matches) use ($camo) {
            return '<img' . $matches[1] . 'src=' . $matches[2] . $camo->camoHttpOnly($matches[3]) . $matches[4] . $matches[5] . '>';
        }, $content);
    }
}
