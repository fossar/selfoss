<?PHP

namespace controllers;

/**
 * Parent Controller
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class BaseController {

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
	
}
