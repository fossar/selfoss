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
     * mark items as read. Allows one id or an array of ids
     * json
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

        // validate id or ids
        if (!$itemDao->isValid('id', $lastid))
            $this->view->error('invalid id');

        $itemDao->mark($lastid);

        $return = array(
            'success' => true
        );

        $this->view->jsonSuccess($return);
    }


    /**
     * mark item as unread
     * json
     *
     * @return void
     */
    public function unmark() {
        $lastid = \F3::get('PARAMS["item"]');

        $itemDao = new \daos\Items();

        if (!$itemDao->isValid('id', $lastid))
            $this->view->error('invalid id');

        $itemDao->unmark($lastid);

        $this->view->jsonSuccess(array(
            'success' => true
        ));
    }


    /**
     * starr item
     * json
     *
     * @return void
     */
    public function starr() {
        $id = \F3::get('PARAMS["item"]');

        $itemDao = new \daos\Items();

        if (!$itemDao->isValid('id', $id))
            $this->view->error('invalid id');

        $itemDao->starr($id);
        $this->view->jsonSuccess(array(
            'success' => true
        ));
    }


    /**
     * unstarr item
     * json
     *
     * @return void
     */
    public function unstarr() {
        $id = \F3::get('PARAMS["item"]');

        $itemDao = new \daos\Items();

        if (!$itemDao->isValid('id', $id))
            $this->view->error('invalid id');

        $itemDao->unstarr($id);
        $this->view->jsonSuccess(array(
            'success' => true
        ));
    }


    /**
     * returns items as json string
     * json
     *
     * @return void
     */
    public function listItems() {
        // parse params
        $options = array();
        if(count($_GET)>0)
            $options = $_GET;

        // get items
        $itemDao = new \daos\Items();
        $items = $itemDao->get($options);

        $this->view->jsonSuccess($items);
    }


    /**
     * returns current basic stats
     * json
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
