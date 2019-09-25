<?php

namespace helpers;

/**
 * Helper class for color handling
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Color {
    /**
     * generate random color
     *
     * @return string random color in format #123456
     */
    public static function randomColor() {
        return '#' . self::randomColorPart() . self::randomColorPart() . self::randomColorPart();
    }

    /**
     * generate random number between 0-255 in hex
     *
     * @return string random color part
     */
    private static function randomColorPart() {
        return str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
    }

    /**
     * get dark OR bright color depending the color contrast
     *
     * @param string $color color (hex) value
     * @param string $darkColor dark color value
     * @param string $brightColor bright color value
     *
     * @return string dark OR bright color value
     *
     * @see https://24ways.org/2010/calculating-color-contrast/
     */
    public static function colorByBrightness($color, $darkColor = '#555', $brightColor = '#EEE') {
        $color = trim($color, '#');
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return ($yiq >= 128) ? $darkColor : $brightColor;
    }
}
