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
class Sources extends BaseController {
    
    /**
     * list all available sources
     *
     * @return void
     */
    public function show() {
        // get available spouts
        $spoutLoader = new \helpers\SpoutLoader();
        $this->view->spouts = $spoutLoader->all();

        $itemDao = new \daos\Items();
        
        // load sources
        $sourcesDao = new \daos\Sources();
        $sourcesHtml = '<div class="source-add"> add source</div> <a class="source-opml" href="opml">or import from opml file or google reader</a>';
        $i=0;
        
        foreach($sourcesDao->get() as $source) {
            $this->view->source = $source;
            $this->view->source['icon'] = $itemDao->getLastIcon($source['id']);
            $sourcesHtml .= $this->view->render('templates/source.phtml');
        }
        
        echo $sourcesHtml;
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
        
        $spout = str_replace("_", "\\", $_GET['spout']);
        $this->view->spout = $spoutLoader->get($spout);
        
        if($this->view->spout===false)
            $this->view->error('invalid spout type given');
        
        if($this->view->spout->params!==false)
            echo $this->view->render('templates/source_params.phtml');
    }
    
    
    /**
     * delete source
     *
     * @return void
     */
    public function remove() {
        $id = \F3::get('PARAMS["id"]');
        
        $sourceDao = new \daos\Sources();
        
        if (!$sourceDao->isValid('id', $id))
            $this->view->error('invalid id given');
        
        $sourceDao->delete($id);
        
        // cleanup tags
        $tagsDao = new \daos\Tags();
        $allTags = $sourceDao->getAllTags();
        $tagsDao->cleanup($allTags);
    }
    
    
    /**
     * render spouts params
     *
     * @return void
     */
    public function write() {
        $sourcesDao = new \daos\Sources();

        // validate
        parse_str(\F3::get('BODY'),$data);

        if(!isset($data['title']))
            $this->view->jsonError(array('title' => 'no data for title given'));
        if(!isset($data['spout']))
            $this->view->jsonError(array('spout' => 'no data for spout given'));
        
        $title = $data['title'];
        $spout = $data['spout'];
        $tags = $data['tags'];

        unset($data['title']);
        unset($data['spout']);
        unset($data['tags']);

        $spout = str_replace("_", "\\", $spout);
        
        $validation = $sourcesDao->validate($title, $spout, $data);
        if($validation!==true)
            $this->view->error( json_encode($validation) );

        // add/edit source
        $id = \F3::get('PARAMS["id"]');
        
        if (!$sourcesDao->isValid('id', $id))
            $id = $sourcesDao->add($title, $tags, $spout, $data);
        else
            $sourcesDao->edit($id, $title, $tags, $spout, $data);
        
        // autocolor tags
        $tagsDao = new \daos\Tags();
        $tags = explode(",",$tags);
        foreach($tags as $tag)
            $tagsDao->autocolorTag(trim($tag)); 
        
        // cleanup tags
        $tagsDao->cleanup($sourcesDao->getAllTags());
        
        $this->view->jsonSuccess(
            array(
                'success' => true,
                'id' => $id
            )
        );
    }

    /**
     * return all Sources suitable for navigation panel
     *
     * @return htmltext
     */
    public function renderSources($sources) {
        $html = "";
        $itemsDao = new \daos\Items();
        foreach($sources as $source) {
            $this->view->source = $source['title'];
            $this->view->sourceid = $source['id'];
            $this->view->unread = $itemsDao->numberOfUnreadForSource($source['id']);
            $html .= $this->view->render('templates/source-nav.phtml');
        }
        
        return $html;
    }

    /**
     * load all available sources and return all Sources suitable 
     * for navigation panel
     *
     * @return htmltext
     */
    public function sourcesListAsString() {
        $sourcesDao = new \daos\Sources();
        $sources = $sourcesDao->get();
        return $this->renderSources($sources);
    }
}