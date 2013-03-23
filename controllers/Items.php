<?PHP

namespace controllers;

/**
 * Controller for item handling
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Items extends BaseController {
    
    /**
     * mark items as read
     *
     * @return void
     */
    public function mark() {
        if(\F3::get('PARAMS["item"]')!=null)
            $lastid = \F3::get('PARAMS["item"]');
        else if(isset($_POST['ids'])) {
            $lastid = $_POST['ids'];
        }
        
        $itemDao = new \daos\Items();
        
        if (!$itemDao->isValid('id', $lastid))
            $this->view->error('invalid id');
        
        $itemDao->mark($lastid);
        
        // get new tag list with updated count values
        $tagController = new \controllers\Tags();
        $renderedTags = $tagController->tagsListAsString();
        
        // get new sources list
        $sourcesController = new \controllers\Sources();
        $renderedSources = $sourcesController->sourcesListAsString();
        
        $this->view->jsonSuccess(array(
            'success' => true,
            'tags'    => $renderedTags,
            'sources' => $renderedSources
        ));
    }
    
    
    /**
     * mark items as unread
     *
     * @return void
     */
    public function unmark() {
        $lastid = \F3::get('PARAMS["item"]');

        $itemDao = new \daos\Items();
        
        if (!$itemDao->isValid('id', $lastid))
            $this->view->error('invalid id');
        
        $itemDao->unmark($lastid);
        $this->view->jsonSuccess(array('success' => true));
    }
    
    
    /**
     * starr item
     *
     * @return void
     */
    public function starr() {
        $id = \F3::get('PARAMS["item"]');

        $itemDao = new \daos\Items();
        
        if (!$itemDao->isValid('id', $id))
            $this->view->error('invalid id');

        $itemDao->starr($id);
        $this->view->jsonSuccess(array('success' => true));
    }
    
    
    /**
     * unstarr item
     *
     * @return void
     */
    public function unstarr() {
        $id = \F3::get('PARAMS["item"]');

        $itemDao = new \daos\Items();
        
        if (!$itemDao->isValid('id', $id))
            $this->view->error('invalid id');

        $itemDao->unstarr($id);
        $this->view->jsonSuccess(array('success' => true));
    }
    
    
    /**
     * update feeds
     *
     * @return void
     */
    public function update() {
        $loader = new \helpers\ContentLoader();
        $loader->update();
        echo "finished";
    }
    
    
    /**
     * returns current stats
     *
     * @return void
     */
    public function stats() {
        $itemsDao = new \daos\Items();
        $return = array(
            'all'     => $itemsDao->numberOfItems(),
            'unread'  => $itemsDao->numberOfUnread(),
            'starred' => $itemsDao->numberOfStarred()
        );
        $this->view->jsonSuccess($return);
    }
}