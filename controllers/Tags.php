<?PHP

namespace controllers;

/**
 * Controller for tag access
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Tags extends BaseController {

    /**
     * returns all tags
     *
     * @return void
     */
    public function tags() {
        $tagsDao = new \daos\Tags();
        echo $this->renderTags($tagsDao->get());
    }
    
    /**
     * set tag color
     *
     * @return void
     */
    public function tagset() {
        $tag = $_POST['tag'];
        $color = $_POST['color'];
        
        if(!isset($tag) || strlen(trim($tag))==0)
            $this->view->error('invalid or no tag given');
        if(!isset($color) || strlen(trim($color))==0)
            $this->view->error('invalid or no color given');
            
        $tagsDao = new \daos\Tags();
        $tagsDao->saveTagColor($tag, $color);
        $this->view->jsonSuccess(array('success' => true));
    }
    
    /**
     * returns all tags
     *
     * @return void
     */
    public function renderTags($tags) {
        $html = "";
        foreach($tags as $tag) {
            $this->view->tag = $tag['tag'];
            $this->view->color = $tag['color'];
            $html .= $this->view->render('templates/tag.phtml');
        }
        
        return $html;
    }
}
