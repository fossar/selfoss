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
class Items {

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
     * mark items as read
     *
     * @return void
     */
    public function mark() {
        $lastid = \F3::get('PARAMS["item"]');

        $itemModel = new \models\Items();
        
        if (!$itemModel->isValid('id', $lastid))
            $this->view->error('invalid id');
        
        $itemModel->mark($lastid);
        $this->view->jsonSuccess(array('success' => true));
    }
    
    
    /**
     * starr item
     *
     * @return void
     */
    public function starr() {
        $id = \F3::get('PARAMS["item"]');

        $itemModel = new \models\Items();
        
        if (!$itemModel->isValid('id', $id))
            $this->view->error('invalid id');

        $itemModel->starr($id);
        $this->view->jsonSuccess(array('success' => true));
    }
    
    
    /**
     * unstarr item
     *
     * @return void
     */
    public function unstarr() {
        $id = \F3::get('PARAMS["item"]');

        $itemModel = new \models\Items();
        
        if (!$itemModel->isValid('id', $id))
            $this->view->error('invalid id');

        $itemModel->unstarr($id);
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
}