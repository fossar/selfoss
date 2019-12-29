<?php

namespace controllers\Opml;

use helpers\Authentication;
use helpers\View;
use SimpleXMLElement;

/**
 * OPML loading and exporting controller
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Michael Moore <stuporglue@gmail.com>
 * @author     Sean Rand <asanernd@gmail.com>
 */
class Import {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var array Sources that have been imported from the OPML file */
    private $imported = [];

    /** @var \daos\Sources */
    private $sourcesDao;

    /** @var \daos\Tags */
    private $tagsDao;

    /** @var View view helper */
    private $view;

    public function __construct(Authentication $authentication, View $view) {
        $this->authentication = $authentication;
        $this->view = $view;
    }

    /**
     * Add an OPML to the user's subscriptions
     * html
     *
     * @note Borrows from controllers/Sources.php:write
     */
    public function add() {
        $this->authentication->needsLoggedIn();

        http_response_code(400);

        /** @var array */
        $messages = [];

        try {
            $opml = $_FILES['opml'];
            if ($opml['error'] === UPLOAD_ERR_NO_FILE) {
                throw new \Exception('No file uploaded!');
            }
            if (!in_array($opml['type'], ['application/xml', 'text/xml', 'text/x-opml+xml', 'text/x-opml'], true)) {
                throw new \Exception('Unsupported file type: ' . $opml['type']);
            }

            $this->sourcesDao = new \daos\Sources();
            $this->tagsDao = new \daos\Tags();

            \F3::get('logger')->debug('start OPML import ');

            $subs = simplexml_load_file($opml['tmp_name']);
            $errors = $this->processGroup($subs->body);

            // cleanup tags
            $this->tagsDao->cleanup($this->sourcesDao->getAllTags());

            \F3::get('logger')->debug('finished OPML import ');

            // show errors
            if (count($errors) > 0) {
                http_response_code(202);
                $messages = 'The following feeds could not be imported:';
                $messages += $errors;
            } else { // On success bring them back to their subscription list
                http_response_code(200);
                $amount = count($this->imported);
                $messages[] = 'Success! ' . $amount . ' feed' . ($amount !== 1 ? 's have' : ' has') . ' been imported.';
            }
        } catch (\Exception $e) {
            $messages[] = $e->getMessage();
        }

        $this->view->jsonSuccess([
            'messages' => $messages,
        ]);
    }

    /**
     * Process a group of outlines
     *
     * - Recursive
     * - We use non-rss outline’s text as tags
     * - Reads outline elements from both the default and selfoss namespace
     *
     * @param SimpleXMLElement $xml A SimpleXML object with <outline> children
     * @param array $tags An array of tags for the current <outline>
     *
     * @return string[] titles of feeds that could not be added to subscriptions
     */
    private function processGroup(SimpleXMLElement $xml, array $tags = []) {
        $errors = [];

        $xml->registerXPathNamespace('selfoss', 'https://selfoss.aditu.de/');

        // tags are the words of the outline parent
        $title = (string) $xml->attributes(null)->title;
        if ($title !== '' && $title !== '/') {
            $tags[] = $title;
            // for new tags, try to import tag color, otherwise use random color
            if (!$this->tagsDao->hasTag($title)) {
                $tagColor = (string) $xml->attributes('selfoss', true)->color;
                if ($tagColor !== '') {
                    $this->tagsDao->saveTagColor($title, $tagColor);
                } else {
                    $this->tagsDao->autocolorTag($title);
                }
            }
        }

        // parse outline items from the default and selfoss namespaces
        foreach ($xml->xpath('outline|selfoss:outline') as $outline) {
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
     * @param SimpleXMLElement $xml xml feed entry for item
     * @param array $tags of the entry
     *
     * @return bool|string true on success or item title on error
     */
    private function addSubscription(SimpleXMLElement $xml, array $tags) {
        // OPML Required attributes: text, xmlUrl, type
        // Optional attributes: title, htmlUrl, language, title, version
        // Selfoss namespaced attributes: spout, params

        $attrs = $xml->attributes(null);
        $nsattrs = $xml->attributes('selfoss', true);

        // description
        $title = (string) $attrs->text;
        if ($title === '') {
            $title = (string) $attrs->title;
        }

        // RSS URL
        $data['url'] = (string) $attrs->xmlUrl;

        // set spout for new item
        if ($nsattrs->spout || $nsattrs->params) {
            if (!($nsattrs->spout && $nsattrs->params)) {
                \F3::get('logger')->warning("OPML import: failed to import '$title'");
                $missingAttr = $nsattrs->spout ? '"selfoss:params"' : '"selfoss:spout"';
                \F3::get('logger')->debug("Missing attribute: $missingAttr");

                return $title;
            }
            $spout = (string) $nsattrs->spout;
            $data = json_decode(html_entity_decode((string) $nsattrs->params), true);
        } elseif (in_array((string) $attrs->type, ['rss', 'atom'], true)) {
            $spout = 'spouts\rss\feed';
        } else {
            \F3::get('logger')->warning("OPML import: failed to import '$title'");
            \F3::get('logger')->debug("Invalid type '$attrs->type': only 'rss' and 'atom' are supported");

            return $title;
        }

        // validate new item
        $validation = @$this->sourcesDao->validate($title, $spout, $data);
        if ($validation !== true) {
            \F3::get('logger')->warning("OPML import: failed to import '$title'");
            \F3::get('logger')->debug('Invalid source', $validation);

            return $title;
        }

        // insert item or update tags for already imported item
        $hash = md5($title . $spout . json_encode($data));
        if (array_key_exists($hash, $this->imported)) {
            $this->imported[$hash]['tags'] = array_unique(array_merge($this->imported[$hash]['tags'], $tags));
            $tags = $this->imported[$hash]['tags'];
            $this->sourcesDao->edit($this->imported[$hash]['id'], $title, $tags, '', $spout, $data);
            \F3::get('logger')->debug("OPML import: updated tags for '$title'");
        } elseif ($id = $this->sourcesDao->checkIfExists($title, $spout, $data)) {
            $tags = array_unique(array_merge($this->sourcesDao->getTags($id), $tags));
            $this->sourcesDao->edit($id, $title, $tags, '', $spout, $data);
            $this->imported[$hash] = ['id' => $id, 'tags' => $tags];
            \F3::get('logger')->debug("OPML import: updated tags for '$title'");
        } else {
            $id = $this->sourcesDao->add($title, $tags, '', $spout, $data);
            $this->imported[$hash] = ['id' => $id, 'tags' => $tags];
            \F3::get('logger')->debug("OPML import: successfully imported '$title'");
        }

        // success
        return true;
    }
}
