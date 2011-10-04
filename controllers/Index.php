<?PHP

namespace controllers;

/**
 * Controller for root
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
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
        if(isset($_GET['logout']))
            \F3::get('auth')->logout();

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
                    if(\F3::get('auth')->login($_POST['username'], md5(\F3::get('salt') . $_POST['password']))===false)
                        $view->error = 'invalid username/password';
                }
            }
            
            // show login
            if(count($_POST)==0 || isset($view->error))
                die($view->render('templates/login.phtml'));
                
        }


        // parse params
        $options = array();
        if(count($_GET)>0)
            $options = $_GET;
        $options['items'] = \F3::get('items_perpage');
        
        // load entries
        $itemModel = new \models\Items();
        $sourcesHtml = "";
        $i=0;
        if(isset($_GET['starred']))
            $view->starred = true;
        if(isset($_GET['search']))
            $view->search = $_GET['search'];
        foreach($itemModel->get($options) as $item) {
            $view->item = $item;
            $view->even = ($i++)%2==0;
            $sourcesHtml .= $view->render('templates/item.phtml');
        }

        if(strlen($sourcesHtml)==0)
            $sourcesHtml = '<div class="stream-empty">no entries found</div>';
        else {
            if($itemModel->hasMore())
                $sourcesHtml .= '<div class="stream-more"><span>more</span></div>';
        }

        if(isset($options['offset']) && $options['offset']>0)
            die($sourcesHtml);

        $view->content = $sourcesHtml;
        $view->publicMode = \F3::get('auth')->isLoggedin()!==true && \F3::get('public')==1;
        $view->loggedin = \F3::get('auth')->isLoggedin()===true;
        echo $view->render('templates/home.phtml');
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
        $itemModel = new \models\Items();
        foreach($itemModel->get($options) as $item) {
            if($newestEntryDate===false)
                $newestEntryDate = $item['datetime'];
            $newItem = $feedWriter->createNewItem();
            $newItem->setTitle(html_entity_decode(utf8_decode($item['title'])));
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
            $itemModel->mark($lastid);
        
        $feedWriter->genarateFeed();
    }
}