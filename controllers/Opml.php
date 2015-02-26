<?PHP

namespace controllers;

/**
 * OPML loading and exporting controller
 *
 * @package    controllers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Michael Moore <stuporglue@gmail.com>
 * @author     Sean Rand <asanernd@gmail.com>
 */

class Opml extends BaseController {

    /**
     * Passed to opml.phtml
     * @var String
     */
    private $msgclass = 'error'; 
    
    /**
     * Passed to opml.phtml
     * @var String
     */
    private $msg;

    /**
    * Sources that have been imported from the OPML file
    * @var Array
    */
    private $imported = array();


    /** 
     * Shows a simple html form
     * html
     *
     */
    public function show() {
        $this->needsLoggedIn();

        $this->view = new \helpers\View();
        $this->view->msg = $this->msg;
        $this->view->msgclass = $this->msgclass;
        echo $this->view->render('templates/opml.phtml');
    }
    
    
    /**
     * Add an OPML to the user's subscriptions
     * html
     *
     * @note Borrows from controllers/Sources.php:write
     */
    public function add() {
        $this->needsLoggedIn();

        try {
            $opml = $_FILES['opml'];
            if ($opml['error'] == UPLOAD_ERR_NO_FILE) {
                throw new \Exception("No file uploaded!");
            }
            if (! in_array($opml['type'], array('application/xml', 'text/xml', 'text/x-opml+xml', 'text/x-opml'))) {
                throw new \Exception("Unsupported file type: " . $opml['type']);
            }

            $this->sourcesDao = new \daos\Sources();
            $this->tagsDao = new \daos\Tags();

            \F3::get('logger')->log('start OPML import ', \DEBUG);

            $subs = simplexml_load_file($opml['tmp_name']);
            $errors = $this->processGroup($subs->body);

            // cleanup tags
            $this->tagsDao->cleanup($this->sourcesDao->getAllTags());

            \F3::get('logger')->log('finished OPML import ', \DEBUG);

            // show errors
            if (count($errors) > 0) {
                $this->msg = "The following feeds could not be imported:<br>";
                $this->msg .= implode("<br>", $errors);
                $this->show();
                
            // On success bring them back to their subscription list
            } else {
                $amount = count($this->imported);
                $this->msg = "Success! " . $amount . " feed" . ($amount!=1?"s have":" has") . " been imported.<br />" .
                             "You might want to <a href='update'>update now</a> or <a href='./'>view your feeds</a>.";
                $this->msgclass = 'success';
                $this->show();
            }
        } catch (\Exception $e) {
            $this->msg = "</p>There was a problem importing your OPML file: <p>";
            $this->msg .= $e->getMessage();
            $this->show();
        }
    }
    
    
    /**
     * Process a group of outlines
     *
     * @param $xml (SimpleXML) A SimpleXML object with <outline> children
     * @param $tags (Array) An array of tags for the current <outline>
     * @note Recursive
     * @note We use non-rss outline's text as tags
     * @note Reads outline elements from both the default and selfoss namespace
     */
    private function processGroup($xml, $tags = Array()) {
        $errors = Array();

        $xml->registerXPathNamespace('selfoss', 'http://selfoss.aditu.de/');

        // tags are the words of the outline parent
        $title = (string)$xml->attributes(null)->title;
        if ($title!=null && $title!='/') {
            $tags[] = $title;
            // for new tags, try to import tag color, otherwise use random color
            if (!$this->tagsDao->hasTag($title)) {
                $tagColor = (string)$xml->attributes('selfoss', true)->color;
                if ($tagColor != null)
                    $this->tagsDao->saveTagColor($title, $tagColor);
                else
                    $this->tagsDao->autocolorTag($title);
            }
        }

        // parse outline items from the default and selfoss namespaces
        foreach ($xml->xpath("outline|selfoss:outline") as $outline) {
            if (count($outline->children()) + count($outline->children('selfoss', true)) > 0) {
                // outline element has children, recurse into it
                $ret = $this->processGroup($outline, $tags);
                $errors = array_merge($errors, $ret);
            } else {
                $ret = $this->addSubscription($outline, $tags);
                if ($ret !== true) {
                    $errors[] = $ret;
                }
            }
        }
        return $errors;
    }
    
    
    /**
     * Add new feed subscription
     *
     * @return true on success or item title on error
     * @param $xml xml feed entry for item
     * @param $tags of the entry
     */
    private function addSubscription($xml, $tags) {
        // OPML Required attributes: text, xmlUrl, type
        // Optional attributes: title, htmlUrl, language, title, version
        // Selfoss namespaced attributes: spout, params

        $attrs = $xml->attributes(null);
        $nsattrs = $xml->attributes('selfoss', true);

        // description
        $title = (string)$attrs->text;
        if ($title == null) {
            $title = (string)$attrs->title;
        }
        
        // RSS URL
        $data['url'] = (string)$attrs->xmlUrl;

        // set spout for new item
        if ($nsattrs->spout || $nsattrs->params) {
            if (!($nsattrs->spout && $nsattrs->params)) {
                \F3::get('logger')->log('OPML import: failed to import      "' . $title . '"', \WARNING);
                \F3::get('logger')->log('                                     missing attribute ' .
                                        ($nsattrs->spout ? '"selfoss:params"' : '"selfoss:spout"'), \DEBUG);
                return $title;
            }
            $spout = (string)$nsattrs->spout;
            $data = json_decode(html_entity_decode((string)$nsattrs->params), true);
        } else if (in_array((string)$attrs->type, array('rss', 'atom'))) {
            $spout = 'spouts\rss\feed';
        } else {
            \F3::get('logger')->log('OPML import: failed to import      "' . $title . '"', \WARNING);
            \F3::get('logger')->log('                                     invalid type "' . (string)$attrs->type .
                                    '": only "rss" and "atom" are supported', \DEBUG);
            return $title;
        }

        // validate new item
        $validation = @$this->sourcesDao->validate($title, $spout, $data);
        if ($validation !== true) {
            \F3::get('logger')->log('OPML import: failed to import      "' . $title . '"', \WARNING);
            foreach ($validation as $id => $param)
                \F3::get('logger')->log('                                     ' . $id . ': ' . $param, \DEBUG);
            return $title;
        }

        // insert item or update tags for already imported item
        $hash = md5($title . $spout . json_encode($data));
        if (array_key_exists($hash, $this->imported)) {
            $this->imported[$hash]['tags'] = array_unique(array_merge($this->imported[$hash]['tags'], $tags));
            $tags = implode(',', $this->imported[$hash]['tags']);
            $this->sourcesDao->edit($this->imported[$hash]['id'], $title, $tags, '', $spout, $data);
            \F3::get('logger')->log('  OPML import: updated tags for      "' . $title . '"', \DEBUG);
        } elseif ($id = $this->sourcesDao->checkIfExists($title, $spout, $data)) {
            $tags = array_unique(array_merge($this->sourcesDao->getTags($id), $tags));
            $this->sourcesDao->edit($id, $title, implode(',', $tags), '', $spout, $data);
            $this->imported[$hash] = Array('id' => $id, 'tags' => $tags);
            \F3::get('logger')->log('  OPML import: updated tags for  "' . $title . '"', \DEBUG);
        } else {
            $id = $this->sourcesDao->add($title, implode(',', $tags), '', $spout, $data);
            $this->imported[$hash] = Array('id' => $id, 'tags' => $tags);
            \F3::get('logger')->log('  OPML import: successfully imported "' . $title . '"', \DEBUG);
        }

        // success
        return true;
    }


    /**
     * Generate an OPML outline element from a source
     *
     * @param $source source
     * @note Uses the selfoss namespace to store information about spouts
     */
    private function writeSource($source) {
        // retrieve the feed url of the source
        $params = json_decode(html_entity_decode($source['params']), true);
        $feedUrl = $source['spout_obj']->getXmlUrl($params);

        // if the spout doesn't return a feed url, the source isn't an RSS feed
        if ($feedUrl !== false)
            $this->writer->startElement('outline');
        else
            $this->writer->startElementNS('selfoss', 'outline', null);

        $this->writer->writeAttribute('title', $source['title']);
        $this->writer->writeAttribute('text', $source['title']);

        if ($feedUrl !== false) {
            $this->writer->writeAttribute('xmlUrl', $feedUrl);
            $this->writer->writeAttribute('type', 'rss');
        }

        // write spout name and parameters in namespaced attributes
        $this->writer->writeAttributeNS('selfoss', 'spout', null, $source['spout']);
        $this->writer->writeAttributeNS('selfoss', 'params', null, html_entity_decode($source['params']));

        $this->writer->endElement();  // outline
        \F3::get('logger')->log("done exporting source ".$source['title'], \DEBUG);
    }


    /**
     * Export user's subscriptions to OPML file
     *
     * @note Uses the selfoss namespace to store selfoss-specific information
     */
    public function export() {
        $this->needsLoggedIn();

        $this->sourcesDao = new \daos\Sources();
        $this->tagsDao = new \daos\Tags();

        \F3::get('logger')->log('start OPML export', \DEBUG);
        $this->writer = new \XMLWriter();
        $this->writer->openMemory();
        $this->writer->setIndent(1);
        $this->writer->setIndentString('    ');

        $this->writer->startDocument('1.0', 'UTF-8');

        $this->writer->startElement('opml');
        $this->writer->writeAttribute('version', '2.0');
        $this->writer->writeAttribute('xmlns:selfoss', 'http://selfoss.aditu.de/');

        // selfoss version, XML format version and creation date
        $this->writer->startElementNS('selfoss', 'meta', null);
        $this->writer->writeAttribute('generatedBy', 'selfoss-' . \F3::get('version'));
        $this->writer->writeAttribute('version', '1.0');
        $this->writer->writeAttribute('createdOn', date('r'));
        $this->writer->endElement();  // meta
        \F3::get('logger')->log('OPML export: finished writing meta', \DEBUG);

        $this->writer->startElement('head');
        $user = \F3::get('username');
        $this->writer->writeElement('title', ($user?$user.'\'s':'My') . ' subscriptions in selfoss');
        $this->writer->endElement();  // head
        \F3::get('logger')->log('OPML export: finished writing head', \DEBUG);

        $this->writer->startElement('body');

        // create tree structure for tagged and untagged sources
        $sources = array('tagged'=>array(), 'untagged'=>array());
        foreach ($this->sourcesDao->get() as $source) {
            if ($source['tags']) {
                foreach (explode(',', $source['tags']) as $tag)
                    $sources['tagged'][$tag][] = $source;
            } else {
                $sources['untagged'][] = $source;
            }
        }

        // create associative array with tag names as keys, colors as values
        $tagColors = array();
        foreach ($this->tagsDao->get() as $key => $tag) {
            \F3::get('logger')->log("OPML export: tag ".$tag['tag']." has color ".$tag['color'], \DEBUG);
            $tagColors[$tag['tag']] = $tag['color'];
        }

        // generate outline elements for all sources
        foreach ($sources['tagged'] as $tag => $children) {
            \F3::get('logger')->log("OPML export: exporting tag $tag sources", \DEBUG);
            $this->writer->startElement('outline');
            $this->writer->writeAttribute('title', $tag);
            $this->writer->writeAttribute('text', $tag);

            $this->writer->writeAttributeNS('selfoss', 'color', null, $tagColors[$tag]);

            foreach ($children as $source) {
                $this->writeSource($source);
            }

            $this->writer->endElement();  // outline
        }

        \F3::get('logger')->log("OPML export: exporting untagged sources", \DEBUG);
        foreach ($sources['untagged'] as $key => $source) {
            $this->writeSource($source);
        }

        $this->writer->endElement();  // body

        $this->writer->endDocument();
        \F3::get('logger')->log('finished OPML export', \DEBUG);

        // save content as file and suggest file name
        header('Content-Disposition: attachment; filename="selfoss-subscriptions.xml"');
        header('Content-Type: text/xml; charset=UTF-8');
        echo $this->writer->outputMemory();
    }
}
