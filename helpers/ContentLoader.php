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
class ContentLoader {
    
    /**
     * ctor
     */
    public function __construct() {
        // include htmLawed
        if(!function_exists('htmLawed'))
            require('libs/htmLawed.php');
    }
    
    
    /**
     * updates all sources
     *
     * @return void
     */
    public function update() {
        $sourcesDao = new \daos\Sources();
        foreach($sourcesDao->get() as $source) {
            $this->fetch($source);
        }
        $this->cleanup();
    }
    
    
    /**
     * updates a given source
     * returns an error or true on success
     *
     * @return void
     * @param mixed $source the current source
     */
    public function fetch($source) {
        
        @set_time_limit(5000);
        @error_reporting(E_ERROR);
        
        // logging
        \F3::get('logger')->log('---', \DEBUG);
        \F3::get('logger')->log('start fetching source "'. $source['title'] . ' (id: '.$source['id'].') ', \DEBUG);
        
        // get spout
        $spoutLoader = new \helpers\SpoutLoader();
        $spout = $spoutLoader->get($source['spout']);
        if($spout===false) {
            \F3::get('logger')->log('unknown spout: ' . $source['spout'], \ERROR);
            return;
        }
        \F3::get('logger')->log('spout successfully loaded: ' . $source['spout'], \DEBUG);
        
        // receive content
        \F3::get('logger')->log('fetch content', \DEBUG);
        try {
            $spout->load(
                json_decode(html_entity_decode($source['params']), true)
            );
        } catch(\exception $e) {
            \F3::get('logger')->log('error loading feed content: ' . $e->getMessage(), \ERROR);
            $sourceDao = new \daos\Sources();
            $sourceDao->error($source['id'], date('Y-m-d H:i:s') . 'error loading feed content: ' . $e->getMessage());
            return;
        }
        
        // current date
        $minDate = new \DateTime();
        $minDate->sub(new \DateInterval('P'.\F3::get('items_lifetime').'D'));
        \F3::get('logger')->log('minimum date: ' . $minDate->format('Y-m-d H:i:s'), \DEBUG);
        
        // insert new items in database
        \F3::get('logger')->log('start item fetching', \DEBUG);
        $itemsDao = new \daos\Items();
        $imageHelper = new \helpers\Image();
        $lasticon = false;
        foreach ($spout as $item) {
            // test date: continue with next if item too old
            $itemDate = new \DateTime($item->getDate());
            if($itemDate < $minDate) {
                \F3::get('logger')->log('item "' . $item->getTitle() . '" (' . $item->getDate() . ') older than '.\F3::get('items_lifetime').' days', \DEBUG);
                continue;
            }
			
			// date in future? Set current date
			$now = new \DateTime();
			if($itemDate > $now)
				$itemDate = $now;
            
            // item already in database?
            if($itemsDao->exists($item->getId())===true)
                continue;
            
            // insert new item
            \F3::get('logger')->log('start insertion of new item "'.$item->getTitle().'"', \DEBUG);
            
            // sanitize content html
            $content = htmLawed(
                $item->getContent(), 
                array(
                    "safe"           => 1,
                    "deny_attribute" => '* -alt -title -src -href',
                    "keep_bad"       => 0,
                    "comment"        => 1,
                    "cdata"          => 1,
                    "elements"       => 'div,p,ul,li,a,img,h1,h2,h3,h4,ol,br,table,tr,td'
                )
            );
            $title = htmLawed($item->getTitle(), array("deny_attribute" => "*", "elements" => "-*"));
            \F3::get('logger')->log('item content sanitized', \DEBUG);
            
            $newItem = array(
                    'title'        => $title,
                    'content'      => $content,
                    'source'       => $source['id'],
                    'datetime'     => $item->getDate(),
                    'uid'          => $item->getId(),
                    'thumbnail'    => $item->getThumbnail(),
                    'icon'         => $item->getIcon(),
                    'link'         => htmLawed($item->getLink(), array("deny_attribute" => "*", "elements" => "-*"))
            );
            
            // save thumbnail
            if(strlen($thumbnail = $item->getThumbnail())!=0) {
                $thumbnailAsPng = $imageHelper->loadImage($thumbnail, 150, 150);
                if($thumbnailAsPng!==false) {
                    file_put_contents(
                        'data/thumbnails/' . md5($thumbnail) . '.png', 
                        $thumbnailAsPng
                    );
                    $newItem['thumbnail'] = md5($thumbnail) . '.png';
                    \F3::get('logger')->log('thumbnail generated: '.$thumbnail, \DEBUG);
                } else {
                    $newItem['thumbnail'] = '';
                    \F3::get('logger')->log('thumbnail generation error: '.$thumbnail, \ERROR);
                }
            }
            
            // save icon
            if(strlen($icon = $item->getIcon())!=0) {
                if($icon==$lasticon) {
                    \F3::get('logger')->log('use last icon: '.$lasticon, \DEBUG);
                    $newItem['icon'] = md5($lasticon) . '.png';
                } else {
                    $iconAsPng = $imageHelper->loadImage($icon, 30, 30);
                    if($iconAsPng!==false) {
                        file_put_contents(
                            'data/favicons/' . md5($icon) . '.png', 
                            $iconAsPng
                        );
                        $newItem['icon'] = md5($icon) . '.png';
                        $lasticon = $icon;
                        \F3::get('logger')->log('icon generated: '.$icon, \DEBUG);
                    } else {
                        $newItem['icon'] = '';
                        \F3::get('logger')->log('icon generation error: '.$icon, \ERROR);
                    }
                }
            }
            
            // insert new item
            $itemsDao->add($newItem);
            \F3::get('logger')->log('item inserted', \DEBUG);
            
            \F3::get('logger')->log('Memory usage: '.memory_get_usage(), \DEBUG);
            \F3::get('logger')->log('Memory peak usage: '.memory_get_peak_usage(), \DEBUG);
        }
    
        // destroy feed object (prevent memory issues)
        \F3::get('logger')->log('destroy spout object', \DEBUG);
        $spout->destroy();
        
        // remove previous error
        if(strlen(trim($source['error']))!=0) {
            $sourceDao = new \daos\Sources();
            $sourceDao->error($source['id'], '');
        }
    }
    
    
    /**
     * clean up messages, thumbnails etc.
     *
     * @return void
     */
    public function cleanup() {
        // cleanup old items
        if(\F3::get('items_lifetime')) {
            \F3::get('logger')->log('cleanup old items', \DEBUG);
            $itemsDao = new \daos\Items();
            $itemsDao->cleanup(\F3::get('items_lifetime'));
            \F3::get('logger')->log('cleanup old items finished', \DEBUG);
        }
        
        // delete orphaned thumbnails
        \F3::get('logger')->log('delete orphaned thumbnails', \DEBUG);
        $this->cleanupFiles('thumbnails');
        \F3::get('logger')->log('delete orphaned thumbnails finished', \DEBUG);
        
        // delete orphaned icons
        \F3::get('logger')->log('delete orphaned icons', \DEBUG);
        $this->cleanupFiles('icons');
        \F3::get('logger')->log('delete orphaned icons finished', \DEBUG);
        
        // optimize database
        \F3::get('logger')->log('optimize database', \DEBUG);
        $database = new \daos\Database();
		$database->optimize();
        \F3::get('logger')->log('optimize database finished', \DEBUG);
    }
    
    
    /**
     * clean up orphaned thumbnails or icons
     *
     * @return void
     * @param string $type thumbnails or icons
     */
    protected function cleanupFiles($type) {
		$itemsDao = new \daos\Items();
		\F3::set('im', $itemsDao);
		if($type=='thumbnails') {
            $checker = function($file) { return \F3::get('im')->hasThumbnail($file);};
            $itemPath = 'data/thumbnails/';
        } else if($type=='icons') {
            $checker = function($file) { return \F3::get('im')->hasIcon($file);};
            $itemPath = 'data/favicons/';
        }
		
		foreach(scandir($itemPath) as $file) {
			if(is_file($itemPath . $file)) {
				$inUsage = $checker($file);
				if($inUsage===false) {
                    unlink($itemPath . $file);
				}
            }
		}
    }
    
}
