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
     * home site
     *
     * @return void
     */
    public function home() {
        $view = new \helpers\View();

        // logout
        if(isset($_GET['logout'])) {
            \F3::get('auth')->logout();
            \F3::reroute(\F3::get('base_url'));
        }

		
        // show login?
        if( 
            isset($_GET['login']) || (\F3::get('auth')->isLoggedin()!==true && \F3::get('public')!=1)
           ) {

            // login?
            if(count($_POST)>0) {
                if(!isset($_POST['username']))
                    $view->error = 'no username given';
                else if(!isset($_POST['password']))
                    $view->error = 'no password given';
                else {
                    if(\F3::get('auth')->login($_POST['username'], $_POST['password'])===false)
                        $view->error = 'invalid username/password';
                }
            }
            
            // show login
            if(count($_POST)==0 || isset($view->error))
                die($view->render('templates/login.phtml'));
            else
                \F3::reroute(\F3::get('base_url'));
                
        }

		
		

        // parse params
        $options = array();
        if(count($_GET)>0)
            $options = $_GET;
        
		// parse params for view
		if(isset($_GET['type']) && $_GET['type']=='starred')
            $view->starred = true;
		else if(isset($_GET['type']) && $_GET['type']=='unread')
            $view->unread = true;
		// todo: add tag
		if(isset($_GET['search']))
            $view->search = $_GET['search'];
			
			
        // load entries
        $itemDao = new \daos\Items();
        $sourcesHtml = "";
        foreach($itemDao->get($options) as $item) {
            $view->item = $item;
            $sourcesHtml .= $view->render('templates/item.phtml');
        }

        if(strlen($sourcesHtml)==0) {
            $sourcesHtml = '<div class="stream-empty">no entries found</div>';
        } else {
            if($itemDao->hasMore())
                $sourcesHtml .= '<div class="stream-more"><span>more</span></div>';
        }

		// just show items html
        if(isset($options['ajax']))
            die($sourcesHtml);

		// show as full html page	
        $view->content = $sourcesHtml;
        $view->publicMode = \F3::get('auth')->isLoggedin()!==true && \F3::get('public')==1;
        $view->loggedin = \F3::get('auth')->isLoggedin()===true;
        echo $view->render('templates/home.phtml');
    }
    
    
    /**
     * password hash generator
     *
     * @return void
     */
    public function password() {
        $view = new \helpers\View();
        $view->password = true;
        if(isset($_POST['password']))
            $view->hash = hash("sha512", \F3::get('salt') . $_POST['password']);
        echo $view->render('templates/login.phtml');
    }
    
    
    /**
     * rss feed
     *
     * @return void
     */
    public function rss() {
        $feedWriter = new \FeedWriter(\RSS2);
        $feedWriter->setTitle(\F3::get('rss_title'));
        
        $view = new \helpers\View();
        $feedWriter->setLink($view->base);
        
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
}
