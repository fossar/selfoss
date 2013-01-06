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
            $searchWords = explode(" ", $searchWords);
        
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
			return round($ageInSeconds, 0) . " seconds ago";
		if($ageInHours<1)
			return round($ageInMinutes, 0) . " minutes ago";
		if($ageInDays<1)
			return round($ageInHours, 0) . " hours ago";
		
		return $datestr;
	}
}