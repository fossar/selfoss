<?PHP

namespace helpers;

/**
 * Helper class for loading extern items
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class ViewHelper {

    /** encloses all searchWords with <span class=found>$word</span>
      * for later highlitning with CSS
      *
      * @return string with highlited words
        * @param string $content which contains words
        * @param array|string $searchWords words for highlighting
      */
    public function highlight($content, $searchWords) {
        if(strlen(trim($searchWords))==0)
            return $content;

        if(!is_array($searchWords))
            $searchWords = \helpers\Search::splitTerms($searchWords);

        foreach($searchWords as $word)
            $content = preg_replace('/(?!<[^<>])('.$word.')(?![^<>]*>)/i','<span class=found>$0</span>',$content);

        return $content;
    }


    /**
     * removes img src attribute and saves the value in ref for
     * loading it later
     *
     * @return string with replaced img tags
     * @param string $content which contains img tags
     */
    public function lazyimg($content) {
        return preg_replace("/<img([^<]+)src=(['\"])([^\"']*)(['\"])([^<]*)>/i","<img$1ref='$3'$5>",$content);
    }


    /**
     * format given date as "x days ago"
     *
     * @return string with replaced formateddate
     * @param
     */
    public function dateago($datestr) {
        $date = new \DateTime($datestr);
        $now = new \DateTime();
        $ageInSeconds = $now->getTimestamp() - $date->getTimestamp();
        $ageInMinutes = $ageInSeconds / 60;
        $ageInHours = $ageInMinutes / 60;
        $ageInDays = $ageInHours / 24;

        if($ageInMinutes<1)
            return \F3::get('lang_seconds',round($ageInSeconds, 0));
        if($ageInHours<1)
            return \F3::get('lang_minutes',round($ageInMinutes, 0));
        if($ageInDays<1)
            return \F3::get('lang_hours',round($ageInHours, 0));

        //return $datestr;
        return \F3::get('lang_timestamp', $date->getTimestamp());
    }

    /**
     * Proxify imgs through atmos/camo when not https
     *
     * @param  string $content item content
     * @return string          item content
     */
    public function camoflauge($content)
    {
        if (empty($content)) {
            return $content;
        }

        $camo = new \WillWashburn\Phpamo\Phpamo(\F3::get('camo_key'), \F3::get('camo_domain'));
        $dom = new \DOMDocument();
        $dom->loadHTML($content);

        foreach ($dom->getElementsByTagName('img') as $item) {
            if ($item->hasAttribute('src')) {
                $src = $item->getAttribute('src');
                $item->setAttribute('src', $camo->camoHttpOnly($src));
            }
        }

        return $dom->saveHTML();
    }
}
