<?php
/**
 * @author Michael Moore <stuporglue@gmail.com>
 * @license GPLv3
 */

namespace controllers;

class Opml extends BaseController{
    /**
    * @classA simple Opml loading controller
    */

    var $msgclass = 'error'; // Passed to opml.phtml
    var $msg; 		     // Passed to opml.phtml	

    /** 
     * @brief Shows a simple html form
    */
    function show(){
	$this->view = new \helpers\View();
	$this->view->msg = $this->msg;
	$this->view->msgclass = $this->msgclass;
	echo $this->view->render('templates/opml.phtml');
    }

    /**
     * @brief Add an Opml to the user's subscriptions
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

	    $subs = simplexml_load_file($opml['tmp_name']);
	    $subs = $subs->body;
	    $errors = $this->processGroup($subs);
	    if(count($errors) > 1){
		$this->msg = "The following feeds were not added:<br>";
		$this->msg .= implode("<br>",$errors);
		$this->show();
	    }else{
		// On success bring them back to their subscription list
		$this->msg = "Success! You might want to <a href='update'>Update now</a> or <a href='./'>view your feeds</a>.";
		$this->msgclass = 'success';
		$this->show();
	    }
	}catch (Exception $e){
	    $this->msg = "</p>There was a problem importing your OPML file: <p>";
	    $this->msg .= $e->getMessage();
	    $this->show();
	}
    }

    /**
     * @brief Process a group of outlines
     * @param $xml (SimpleXML) A SimpleXML object with <outline> children
     * @param $tags (Array) An array of tags for the current <outline>
     * @note Recursive
     * @note We use non-rss outline's text as tags
     */
    function processGroup($xml,$tags = Array()){
	$errors = Array();
	if((string)$xml['title']){
	    $tags[] = (string)$xml['title'];
	}
	foreach($xml->outline as $outline){
	    if((string)$outline['type']){
		$ret = $this->addSubscription($outline,$tags);
		if($ret!==TRUE)
		    $errors[] = $ret;
	    }else{
		$ret = $this->processGroup($outline,$tags);
		$errors = array_merge($errors,$ret);
	    }
	}
	return $errors;
    }

    function addSubscription($xml,$tags){
	// OPML Required attributes: text,xmlUrl,type
	// Optional attributes: title, htmlUrl, language, title, version
	$title = (string)$xml['text']; 	       // description
	$data['url'] = (string)$xml['xmlUrl']; // RSS URL

	if($xml['type'] == 'rss'){
	    $spout = 'spouts\rss\feed';
	}

	$validation = $this->sourcesDao->validate($title, $spout, $data);

	if($validation!==true)
	    return $title;

	$tags = implode(',',$tags);
	error_log("Tags are: $tags");
	$id = $this->sourcesDao->add($title, $tags, $spout, $data);
	$tags = explode(",",$tags);
	foreach($tags as $tag)
	    $this->tagsDao->autocolorTag(trim($tag)); 

	// cleanup tags
	$this->tagsDao->cleanup($this->sourcesDao->getAllTags());

	return TRUE;
    }
}
