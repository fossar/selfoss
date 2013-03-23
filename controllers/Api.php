<?PHP

namespace controllers;

/**
 * Controller for sources handling
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Api extends BaseController {

    /**
     * login for api json access
     *
     * @return void
     */
    public function login() {
        $view = new \helpers\View();
        if(\F3::get('auth')->isLoggedin()==true)
            $view->jsonSuccess(array('success' => true));
        
        $username = isset($_POST["username"]) ? $_POST["username"] : '';
        $password = isset($_POST["password"]) ? $_POST["password"] : '';
        
        if(\F3::get('auth')->login($username,$password)==true)
            $view->jsonSuccess(array('success' => true));
        
        $view->jsonSuccess(array('success' => false));
    }
    

    /**
     * logout for api json access
     *
     * @return void
     */
    public function logout() {
        $view = new \helpers\View();
        \F3::get('auth')->logout();
        $view->jsonSuccess(array('success' => true));
    }


    /**
     * returns items as json string
     *
     * @return void
     */
    public function items() {
        $options = array();
        if(count($_REQUEST)>0)
            $options = $_REQUEST;
        $options['starred'] = isset($options['starred']) ? $options['starred']=="true" : false;
        $options['offset'] = isset($options['offset']) ? (int)($options['offset']) : 0;
        $options['items'] = isset($options['items']) ? (int)($options['items']) : \F3::get('items_perpage');
        
        $itemDao = new \daos\Items();
        $items = $itemDao->get($options);
        
        if(isset($options['ids']) && is_array($options['ids'])) {
            $itemsWithoutIds = array();
            
            for($i=0; $i<count($options['ids']); $i++)
                $options['ids'][$i] = (int)$options['ids'][$i];
            
            foreach($items as $item) {
                if(in_array($item['id'], $options['ids'])===false) {
                    $itemsWithoutIds[] = $item;
                }
            }
            
            $items = $itemsWithoutIds;
        }
        
        $this->view->jsonSuccess($items);
    }
    
 
}