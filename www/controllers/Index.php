<?PHP

namespace controllers;

/**
 * Controller for root
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Index {

	/**
     * view helper
     *
     * @var helpers_View
     */
    protected $view;

    
    /**
     * initialize controller
     *
     * @return void
     */
    public function __construct() {
        $this->view = new \helpers\View();
    }
	
    /**
     * home site
     *
     * @return void
     */
    public function home() {
        $this->view = new \helpers\View();

        // logout
        if(isset($_GET['logout'])) {
            \F3::get('auth')->logout();
            \F3::reroute(\F3::get('base_url'));
        }

		// check login
		$this->login();
		
        // parse params
        $options = array();
        if(count($_GET)>0)
            $options = $_GET;
        
		// parse params for view
		if(isset($_GET['type']) && $_GET['type']=='starred')
            $this->view->starred = true;
		else if(isset($_GET['type']) && $_GET['type']=='unread')
            $this->view->unread = true;
		// todo: add tag
		if(isset($_GET['search']))
            $this->view->search = $_GET['search'];
		
		// load tags
		$tagsDao = new \daos\Tags();
		$tags = $tagsDao->get();
		
        // load items
		$itemsHtml = $this->loadItems($options, $tags);

		// just show items html
        if(isset($options['ajax']))
            die($itemsHtml);
		
		// show tags
		$tagsController = new \controllers\Tags();
		$this->view->tags = $tagsController->renderTags($tags);
		
		// show as full html page	
        $this->view->content = $itemsHtml;
        $this->view->publicMode = \F3::get('auth')->isLoggedin()!==true && \F3::get('public')==1;
        $this->view->loggedin = \F3::get('auth')->isLoggedin()===true;
        echo $this->view->render('templates/home.phtml');
    }
    
    
    /**
     * password hash generator
     *
     * @return void
     */
    public function password() {
        $this->view = new \helpers\View();
        $this->view->password = true;
        if(isset($_POST['password']))
            $this->view->hash = hash("sha512", \F3::get('salt') . $_POST['password']);
        echo $this->view->render('templates/login.phtml');
    }
    
    
    /**
     * rss feed
     *
     * @return void
     */
    public function rss() {
        $feedWriter = new \FeedWriter(\RSS2);
        $feedWriter->setTitle(\F3::get('rss_title'));
        
        $feedWriter->setLink($this->view->base);
        
        // set options
        $options = array();
        if(count($_GET)>0)
            $options = $_GET;
        $options['items'] = \F3::get('rss_max_items');
        
        // get items
        $newestEntryDate = false;
        $lastid = -1;
        $itemDao = new \daos\Items();
        foreach($itemDao->get($options) as $item) {
            if($newestEntryDate===false)
                $newestEntryDate = $item['datetime'];
            $newItem = $feedWriter->createNewItem();
            $newItem->setTitle(str_replace('&', '&amp;', html_entity_decode(utf8_decode($item['title']))));
            @$newItem->setLink($item['link']);
            $newItem->setDate($item['datetime']);
            $newItem->setDescription(str_replace('&#34;', '"', $item['content']));
            $feedWriter->addItem($newItem);
            $lastid = $item['id'];
        }
        
        if($newestEntryDate===false)
            $newestEntryDate = date(\DATE_ATOM , time());
        $feedWriter->setChannelElement('updated', $newestEntryDate);
        
        // mark as read
        if(\F3::get('rss_mark_as_read')==1 && $lastid!=-1)
            $itemDao->mark($lastid);
        
        $feedWriter->genarateFeed();
    }
	
	
	/**
     * check and show login
     *
     * @return void
     */
	private function login() {
        if( 
            isset($_GET['login']) || (\F3::get('auth')->isLoggedin()!==true && \F3::get('public')!=1)
           ) {

            // login?
            if(count($_POST)>0) {
                if(!isset($_POST['username']))
                    $this->view->error = 'no username given';
                else if(!isset($_POST['password']))
                    $this->view->error = 'no password given';
                else {
                    if(\F3::get('auth')->login($_POST['username'], $_POST['password'])===false)
                        $this->view->error = 'invalid username/password';
                }
            }
            
            // show login
            if(count($_POST)==0 || isset($this->view->error))
                die($this->view->render('templates/login.phtml'));
            else
                \F3::reroute(\F3::get('base_url'));
        }
	}
	
	
	/**
     * load items
     *
     * @return html with items
     */
	private function loadItems($options, $tags) {
		$tagColors = $this->convertTagsToAssocArray($tags);
		
	    $itemDao = new \daos\Items();
        $itemsHtml = "";
        foreach($itemDao->get($options) as $item) {
		
			// parse tags and assign tag colors
			$itemsTags = explode(",",$item['tags']);
			$item['tags'] = array();
			foreach($itemsTags as $tag) {
				$tag = trim($tag);
				if(strlen($tag)>0 && isset($tagColors[$tag]))
					$item['tags'][$tag] = $tagColors[$tag];
			}
			
            $this->view->item = $item;
            $itemsHtml .= $this->view->render('templates/item.phtml');
        }

        if(strlen($itemsHtml)==0) {
            $itemsHtml = '<div class="stream-empty">no entries found</div>';
        } else {
            if($itemDao->hasMore())
                $itemsHtml .= '<div class="stream-more"><span>more</span></div>';
        }
		
		return $itemsHtml;
	}
	
	
	/**
     * return tag => color array
     *
     * @return tag color array
	 * @param array $tags
     */
	private function convertTagsToAssocArray($tags) {
		$assocTags = array();
		foreach($tags as $tag)
			$assocTags[$tag['tag']] = $tag['color'];
		return $assocTags;
	}
}
