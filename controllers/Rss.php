<?PHP

namespace controllers;

/**
 * Controller for rss access
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Rss extends BaseController {
    
    /**
     * rss feed
     *
     * @return void
     */
    public function rss() {
        $this->needsLoggedInOrPublicMode();

        $feedWriter = new \RSS2FeedWriter();
        $feedWriter->setTitle(\F3::get('rss_title'));
        
        $feedWriter->setLink($this->view->base);

        // get sources
        $sourceDao = new \daos\Sources();
        $lastSourceId = 0;
        $lastSourceName = "";

        // set options
        $options = array();
        if(count($_GET)>0)
            $options = $_GET;
        $options['items'] = \F3::get('rss_max_items');
        if(\F3::get('PARAMS["tag"]')!=null)
            $options['tag'] = \F3::get('PARAMS["tag"]');
        if(\F3::get('PARAMS["type"]')!=null)
            $options['type'] = \F3::get('PARAMS["type"]');
            
        
        // get items
        $newestEntryDate = false;
        $lastid = -1;
        $itemDao = new \daos\Items();
        foreach($itemDao->get($options) as $item) {
            if($newestEntryDate===false)
                $newestEntryDate = $item['datetime'];
            $newItem = $feedWriter->createNewItem();
            
            // get Source Name
            if ($item['source'] != $lastSourceId){
                foreach($sourceDao->get() as $source) {
                    if ($source['id'] == $item['source']) {
                        $lastSourceId = $source['id'];
                        $lastSourceName = $source['title'];
                        break;
                    }  
                }  
            }

            $newItem->setTitle(str_replace('&', '&amp;', $this->UTF8entities($item['title'] . " (" . $lastSourceName . ")")));
            @$newItem->setLink($item['link']);
            $newItem->setDate($item['datetime']);
            $newItem->setDescription(str_replace('&#34;', '"', $item['content']));
            
            // add tags in category node
            $itemsTags = explode(",",$item['tags']);
            foreach($itemsTags as $tag) {
                $tag = trim($tag);
                if(strlen($tag)>0)
                    $newItem->addElement('category', $tag);
            }

            $feedWriter->addItem($newItem);
            $lastid = $item['id'];
         
            // mark as read
            if(\F3::get('rss_mark_as_read')==1 && $lastid!=-1)
                $itemDao->mark($lastid);
        }
        
        if($newestEntryDate===false)
            $newestEntryDate = date(\DATE_ATOM , time());
        $feedWriter->setChannelElement('updated', $newestEntryDate);

        
        $feedWriter->generateFeed();
    }

    private function UTF8entities($content="") { 
        $contents = $this->unicode_string_to_array($content);
        $swap = "";
        $iCount = count($contents);
        for ($o=0;$o<$iCount;$o++) {
            $contents[$o] = $this->unicode_entity_replace($contents[$o]);
            $swap .= $contents[$o];
        }
        return html_entity_decode($swap, ENT_NOQUOTES, 'UTF-8'); //convert HTML-entities like &#8211; to UTF-8
        
    }

    private function unicode_string_to_array( $string ) { //adjwilli
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr( $string, 0, 1, "UTF-8" );
            $string = mb_substr( $string, 1, $strlen, "UTF-8" );
            $strlen = mb_strlen( $string );
        }
        return $array;
    }

    private function unicode_entity_replace($c) { //m. perez 
        $h = ord($c{0});    
        if ($h <= 0x7F) { 
            return $c;
        } else if ($h < 0xC2) { 
            return $c;
        }
        
        if ($h <= 0xDF) {
            $h = ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
            $h = "&#" . $h . ";";
            return $h; 
        } else if ($h <= 0xEF) {
            $h = ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6 | (ord($c{2}) & 0x3F);
            $h = "&#" . $h . ";";
            return $h;
        } else if ($h <= 0xF4) {
            $h = ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12 | (ord($c{2}) & 0x3F) << 6 | (ord($c{3}) & 0x3F);
            $h = "&#" . $h . ";";
            return $h;
        }
    }
    
}
