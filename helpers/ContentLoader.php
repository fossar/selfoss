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
     * @var \daos\Items database access for saving new item
     */
    private $itemsDao;

    /**
     * @var \daos\Sourcesdatabase access for saveing sources last update
     */
    private $sourceDao;

    /**
     * ctor
     */
    public function __construct() {
        // include htmLawed
        if(!function_exists('htmLawed'))
            require('libs/htmLawed.php');

        $this->itemsDao = new \daos\Items();
        $this->sourceDao = new \daos\Sources();
    }
    
    
    /**
     * updates all sources
     *
     * @return void
     */
    public function update() {
        $sourcesDao = new \daos\Sources();
        foreach($sourcesDao->getByLastUpdate() as $source) {
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
        
        // at least 20 seconds wait until next update of a given source
        $this->updateSource($source);
        if(time() - $source['lastupdate'] < 20)
            return;
        
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
            \F3::get('logger')->log('error loading feed content for ' . $source['title'] . ': ' . $e->getMessage(), \ERROR);
            $this->sourceDao->error($source['id'], date('Y-m-d H:i:s') . 'error loading feed content: ' . $e->getMessage());
            return;
        }
        
        // current date
        $minDate = new \DateTime();
        $minDate->sub(new \DateInterval('P'.\F3::get('items_lifetime').'D'));
        \F3::get('logger')->log('minimum date: ' . $minDate->format('Y-m-d H:i:s'), \DEBUG);
        
        // insert new items in database
        \F3::get('logger')->log('start item fetching', \DEBUG);

        $itemsInFeed = array();
        foreach ($spout as $item) {
            $itemsInFeed[] = $item->getId();
        }
        $itemsFound = $this->itemsDao->findAll($itemsInFeed);


        $lasticon = false;
        foreach ($spout as $item) {
            // item already in database?
            if (isset($itemsFound[$item->getId()])) {
                continue;
            }
            
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
            
            // insert new item
            \F3::get('logger')->log('start insertion of new item "'.$item->getTitle().'"', \DEBUG);
            
            $content = "";
            try {
                // fetch content
                $content = $item->getContent();
                
                // sanitize content html
                $content = $this->sanitizeContent($content);
            } catch(\exception $e) {
                $content = 'Error: Content not fetched. Reason: ' . $e->getMessage();
                \F3::get('logger')->log('Can not fetch "'.$item->getTitle().'" : ' . $e->getMessage(), \ERROR);
            }

            // sanitize title
            $title = $this->sanitizeField($item->getTitle());
            if(strlen(trim($title))==0)
                $title = "[" . \F3::get('lang_no_title') . "]";

            // Check sanatized title against filter
            try {
                if($this->filter($source, $title,$content)===false)
                    continue;
            } catch(Exception $e) {
                $messagesModel->add($feed, 'filter error');
                continue;
            }


            // sanitize author
            $author = $this->sanitizeField($item->getAuthor());

            \F3::get('logger')->log('item content sanitized', \DEBUG);

            try {
                $icon = $item->getIcon();
            } catch(\exception $e) {
                return;
            }

            $newItem = array(
                    'title'        => $title,
                    'content'      => $content,
                    'source'       => $source['id'],
                    'datetime'     => $itemDate->format('Y-m-d H:i:s'),
                    'uid'          => $item->getId(),
                    'thumbnail'    => $item->getThumbnail(),
                    'icon'         => $icon!==false ? $icon : "",
                    'link'         => htmLawed($item->getLink(), array("deny_attribute" => "*", "elements" => "-*")),
                    'author'       => $author
            );
            
            // save thumbnail
            $newItem = $this->fetchThumbnail($item->getThumbnail(), $newItem);

            // save icon
            $newItem = $this->fetchIcon($item->getIcon(), $newItem, $lasticon);

            // insert new item
            $this->itemsDao->add($newItem);
            \F3::get('logger')->log('item inserted', \DEBUG);
            
            \F3::get('logger')->log('Memory usage: '.memory_get_usage(), \DEBUG);
            \F3::get('logger')->log('Memory peak usage: '.memory_get_peak_usage(), \DEBUG);
        }
    
        // destroy feed object (prevent memory issues)
        \F3::get('logger')->log('destroy spout object', \DEBUG);
        $spout->destroy();

        // remove previous errors and set last update timestamp
        $this->updateSource($source);
    }

    /**
     * Check if a new item matches the filter
     *
     * @param $feed object and new item to add
     * @return boolean indicating filter success
     */
    protected function filter($source, $title,$content) {
        if(strlen(trim($source['filter']))!=0) {
            $resultTitle = @preg_match($source['filter'], $title);
            $resultContent = @preg_match($source['filter'], $content);
            if($resultTitle===false || $resultContent===false) {
                \F3::get('logger')->log('filter error: ' . $source->fiter, \ERROR);
                throw new Exception();
            }
            // test filter
            if($resultTitle==0 && $resultContent==0)
                return false;
        }
        return true;
    }

    /**
     * Sanitize content for preventing XSS attacks.
     *
     * @param $content content of the given feed
     * @return mixed|string sanitized content
     */
    protected function sanitizeContent($content) {
        return htmLawed(
            $content,
            array(
                "safe"           => 1,
                "deny_attribute" => '* -alt -title -src -href -target -width -height, img +width +height',
                "keep_bad"       => 0,
                "comment"        => 1,
                "cdata"          => 1,
                "elements"       => 'div,p,ul,li,a,img,dl,dt,dd,h1,h2,h3,h4,h5,h6,ol,br,table,tr,td,blockquote,pre,ins,del,th,thead,tbody,b,i,strong,em,tt,sub,sup,s,code'
            )
        );
    }

    /**
     * Sanitize a simple field
     *
     * @param $value content of the given field
     * @return mixed|string sanitized content
     */
    protected function sanitizeField($value) {
        return htmLawed(
            htmlspecialchars_decode($value),
            array(
                "deny_attribute" => '* -href -title',
                "elements"       => 'a,br,ins,del,b,i,strong,em,tt,sub,sup,s,code'
            )
        );
    }

    /**
     * Fetch the thumbanil of a given item
     *
     * @param $thumbnail the thumbnail url
     * @param $newItem new item for saving in database
     * @return the newItem Object with thumbnail
     */
    protected function fetchThumbnail($thumbnail, $newItem) {
        if (strlen(trim($thumbnail)) > 0) {
            $imageHelper = new \helpers\Image();
            $thumbnailAsPng = $imageHelper->loadImage($thumbnail, 500, 500);
            if ($thumbnailAsPng !== false) {
                file_put_contents(
                    'data/thumbnails/' . md5($thumbnail) . '.png',
                    $thumbnailAsPng
                );
                $newItem['thumbnail'] = md5($thumbnail) . '.png';
                \F3::get('logger')->log('thumbnail generated: ' . $thumbnail, \DEBUG);
            } else {
                $newItem['thumbnail'] = '';
                \F3::get('logger')->log('thumbnail generation error: ' . $thumbnail, \ERROR);
            }
        }

        return $newItem;
    }


    /**
     * Fetch the icon of a given feed item
     *
     * @param $icon icon given by the spout
     * @param $newItem new item for saving in database
     * @param $lasticon the last fetched icon (byref)
     * @return mixed newItem with icon
     */
    protected function fetchIcon($icon, $newItem, &$lasticon) {
        if(strlen(trim($icon)) > 0) {
            if($icon==$lasticon) {
                \F3::get('logger')->log('use last icon: '.$lasticon, \DEBUG);
                $newItem['icon'] = md5($lasticon) . '.png';
            } else {
                $imageHelper = new \helpers\Image();
                $iconAsPng = $imageHelper->loadImage($icon, 30, null);
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
        return $newItem;
    }


    /**
     * clean up messages, thumbnails etc.
     *
     * @return void
     */
    public function cleanup() {
        // cleanup orphaned and old items
        \F3::get('logger')->log('cleanup orphaned and old items', \DEBUG);
        $this->itemsDao->cleanup(\F3::get('items_lifetime'));
        \F3::get('logger')->log('cleanup orphaned and old items finished', \DEBUG);
        
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
        \F3::set('im', $this->itemsDao);
        if($type=='thumbnails') {
            $checker = function($file) { return \F3::get('im')->hasThumbnail($file);};
            $itemPath = 'data/thumbnails/';
        } else if($type=='icons') {
            $checker = function($file) { return \F3::get('im')->hasIcon($file);};
            $itemPath = 'data/favicons/';
        }
        
        foreach(scandir($itemPath) as $file) {
            if(is_file($itemPath . $file) && $file!=".htaccess") {
                $inUsage = $checker($file);
                if($inUsage===false) {
                    unlink($itemPath . $file);
                }
            }
        }
    }


    /**
     * Update source (remove previous errors, update last update)
     *
     * @param $source source object
     */
    protected function updateSource($source) {
        // remove previous error
        if ( !is_null($source['error']) ) {
            $this->sourceDao->error($source['id'], '');
        }
        // save last update
        $this->sourceDao->saveLastUpdate($source['id']);
    }
}
