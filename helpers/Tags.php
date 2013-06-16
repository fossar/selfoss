<?PHP
namespace helpers;

/**
 * Helper class for tags handling
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Tags {

    /**
     * return tag => color array
     *
     * @return tag color array
     * @param array $tags
     */
    public static function convertToAssocArray($tags) {
        $assocTags = array();
        foreach($tags as $tag)
            $assocTags[$tag['tag']] = $tag['color'];
        return $assocTags;
    }

    /**
     * parse tags and assign tag colors
     *
     * @return tag array with colors
     * @param string $value tags string
     * @param array $tags tag => color array
     */
    public static function parseAndAssignTagColors($value, $tagColors) {
        $tags = explode(",",$value);
        $return = array();
        foreach($tags as $tag) {
            $tag = trim($tag);
            if(strlen($tag)>0 && isset($tagColors[$tag]))
                $return[$tag] = $tagColors[$tag];
        }
        return $return;
    }

}