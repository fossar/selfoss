<?php
/**
 * Simple Opml loading controller
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Michael Moore <stuporglue@gmail.com>
 */
namespace controllers;

class Opml extends BaseController {

    /**
     * Passed to opml.phtml
     * @var String
     */
    var $msgclass = 'error'; 
    
    /**
     * Passed to opml.phtml
     * @var String
     */
    var $msg;
    
    
    /** 
     * Shows a simple html form
    */
    function show(){
        $this->view = new \helpers\View();
        $this->view->msg = $this->msg;
        $this->view->msgclass = $this->msgclass;
        echo $this->view->render('templates/opml.phtml');
    }
    
    
    /**
     * Add an Opml to the user's subscriptions
     * @note Borrows from controllers/Sources.php:write
     */
    function add(){
        try {
            if(!array_key_exists('opml',$_FILES)){
                throw new Exception("No file uploaded!");
            }
            $opml = $_FILES['opml'];
            if(!$opml['type'] == 'text/xml'){
                throw new Exception("Unsupported file type!: " . $opml['type']);
            }

            $this->sourcesDao = new \daos\Sources();
            $this->tagsDao = new \daos\Tags();

            \F3::get('logger')->log('start opml import ', \DEBUG);
            $subs = simplexml_load_file($opml['tmp_name']);
            $subs = $subs->body;
            $errors = $this->processGroup($subs);
            
            // show errors
            if(count($errors) > 1){
                $this->msg = "The following feeds were not added:<br>";
                $this->msg .= implode("<br>",$errors);
                $this->show();
                
            // On success bring them back to their subscription list
            } else {
                $this->msg = "Success! You might want to <a href='update'>Update now</a> or <a href='./'>view your feeds</a>.";
                $this->msgclass = 'success';
                $this->show();
            }
        } catch (Exception $e){
            $this->msg = "</p>There was a problem importing your OPML file: <p>";
            $this->msg .= $e->getMessage();
            $this->show();
        }
    }
    
    
    /**
     * Process a group of outlines
     * @param $xml (SimpleXML) A SimpleXML object with <outline> children
     * @param $tags (Array) An array of tags for the current <outline>
     * @note Recursive
     * @note We use non-rss outline's text as tags
     */
    function processGroup($xml,$tags = Array()){
        $errors = Array();
        
        // tags are the words of the outline parent
        if((string)$xml['title']){
            $tags[] = (string)$xml['title'];
        }
        
        // parse every outline item
        foreach($xml->outline as $outline){
            if((string)$outline['type']) {
                $ret = $this->addSubscription($outline,$tags);
                if($ret!==true) {
                    $errors[] = $ret;
                }
            } else {
                $ret = $this->processGroup($outline,$tags);
                $errors = array_merge($errors,$ret);
            }
        }
        return $errors;
    }
    
    
    /**
     * Add new feed subscription
     * @return true on success or item title on error
     * @param $xml xml feed entry for item
     * @param $tags of the entry
     */
    function addSubscription($xml, $tags){
        // OPML Required attributes: text,xmlUrl,type 
        // Optional attributes: title, htmlUrl, language, title, version
        
        // description
        $title = (string)$xml['text'];
        
        // RSS URL
        $data['url'] = (string)$xml['xmlUrl'];

        if($xml['type'] == 'rss')
            $spout = 'spouts\rss\feed';
        
        // validate new item
        $validation = $this->sourcesDao->validate($title, 'spouts\rss\feed', $data);
        if($validation!==true) {
            \F3::get('logger')->log('opml import: invalid item ' . $title, \DEBUG);
            return $title;
        }

        // import tags
        $tags = implode(',',$tags);
        $id = $this->sourcesDao->add($title, $tags, $spout, $data);
        $tags = explode(",",$tags);
        foreach($tags as $tag)
            $this->tagsDao->autocolorTag(trim($tag)); 
        
        // cleanup tags
        $this->tagsDao->cleanup($this->sourcesDao->getAllTags());

        // success
        \F3::get('logger')->log('opml import: item successfully imported: ' . $title, \DEBUG);
        return true;
    }
}
