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
class Sources {

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
     * list all available sources
     *
     * @return void
     */
    public function show() {

        // get available spouts
        $spoutLoader = new \helpers\SpoutLoader();
        $this->view->spouts = $spoutLoader->all();

        // load sources
        $sourcesModel = new \models\Sources();
        $sourcesHtml = "";
        $i=0;
        foreach($sourcesModel->get() as $source) {
            $this->view->source = $source;
            $this->view->even = ($i++)%2==0;
            $sourcesHtml .= $this->view->render('templates/source.phtml');
        }

        $sourcesHtml .= '<div class="source-add"> add source</div>';

        $this->view->content = $sourcesHtml;
        $this->view->sources = true;
        $this->view->publicMode = \F3::get('auth')->isLoggedin()!==true && \F3::get('public')==1;
        $this->view->loggedin = \F3::get('auth')->isLoggedin()===true;
        echo $this->view->render('templates/home.phtml');
    }
    
    
    /**
     * add new source
     *
     * @return void
     */
    public function add() {
        $spoutLoader = new \helpers\SpoutLoader();
        $this->view->spouts = $spoutLoader->all();
        echo $this->view->render('templates/source.phtml');
    }
    
    
    /**
     * render spouts params
     *
     * @return void
     */
    public function params() {
        if(!isset($_GET['spout']))
            $this->view->error('no spout type given');
        
        $spoutLoader = new \helpers\SpoutLoader();
        $this->view->spout = $spoutLoader->get($_GET['spout']);
        
        if($this->view->spout===false)
            $this->view->error('invalid spout type given');
        
        if($this->view->spout->params!==false)
            echo $this->view->render('templates/source_params.phtml');
    }
    
    
    /**
     * render spouts params
     *
     * @return void
     */
    public function remove() {
        $id = \F3::get('PARAMS["id"]');
        
        $sourceModel = new \models\Sources();
        
        if (!$sourceModel->isValid('id', $id))
            $this->view->error('invalid id given');

        $sourceModel->delete($id);
    }
    
    
    /**
     * render spouts params
     *
     * @return void
     */
    public function write() {
        $sourcesModel = new \models\Sources();

        // validate
        parse_str(\F3::get('REQBODY'),$data);

        if(!isset($data['title']))
            $this->view->jsonError(array('title' => 'no data for title given'));
        if(!isset($data['spout']))
            $this->view->jsonError(array('spout' => 'no data for spout given'));
        
        $title = $data['title'];
        $spout = $data['spout'];

        unset($data['title']);
        unset($data['spout']);

        $spout = str_replace("\\\\", "\\", $spout);
        
        $validation = $sourcesModel->validate($title, $spout, $data);
        if($validation!==true)
            $this->view->error( json_encode($validation) );

        // add/edit source
        $id = \F3::get('PARAMS["id"]');
        
        if (!$sourcesModel->isValid('id', $id))
            $id = $sourcesModel->add($title, $spout, $data);
        else
            $sourcesModel->edit($id, $title, $spout, $data);
            
        $this->view->jsonSuccess(
            array(
                'success' => true,
                'id' => $id
            )
        );
    }
}