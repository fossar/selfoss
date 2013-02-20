<?PHP
namespace helpers;

/**
 * Helper class for color handling
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Color {

    /**
     * generate random color
     *
     * @return string random color in format #123456
     */
    public static function randomColor() {
        return "#" . Color::randomColorPart() . Color::randomColorPart() . Color::randomColorPart();
    }
    
    /**
     * generate random number between 0-255 in hex
     *
     * @return string random color part
     */
    private static function randomColorPart() {
        return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }
    
}