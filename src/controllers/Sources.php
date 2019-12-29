<?php

namespace controllers;

use Base;
use helpers\Authentication;
use helpers\View;

/**
 * Controller for sources handling
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Sources {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var View view helper */
    private $view;

    public function __construct(Authentication $authentication, View $view) {
        $this->authentication = $authentication;
        $this->view = $view;
    }

    /**
     * list all available sources
     * html
     *
     * @return void
     */
    public function show() {
        $this->authentication->needsLoggedIn();

        // get available spouts
        $spoutLoader = new \helpers\SpoutLoader();
        $this->view->spouts = $spoutLoader->all();

        // load sources
        $sourcesDao = new \daos\Sources();
        echo '<button class="source-add">' . \F3::get('lang_source_add') . '</button>' .
             '<a class="source-export" href="opmlexport">' . \F3::get('lang_source_export') . '</a>' .
             '<a class="source-opml" href="opml">' . \F3::get('lang_source_opml');
        $sourcesHtml = '</a>';

        foreach ($sourcesDao->getWithIcon() as $source) {
            $this->view->source = $source;
            $sourcesHtml .= $this->view->render('src/templates/source.phtml');
        }

        echo $sourcesHtml;
    }

    /**
     * add new source
     * html
     *
     * @return void
     */
    public function add() {
        $this->authentication->needsLoggedIn();

        $spoutLoader = new \helpers\SpoutLoader();
        $this->view->spouts = $spoutLoader->all();
        echo $this->view->render('src/templates/source.phtml');
    }

    /**
     * render spouts params
     * html
     *
     * @return void
     */
    public function params() {
        $this->authentication->needsLoggedIn();

        if (!isset($_GET['spout'])) {
            $this->view->error('no spout type given');
        }

        $spoutLoader = new \helpers\SpoutLoader();

        $spout = str_replace('_', '\\', $_GET['spout']);
        $this->view->spout = $spoutLoader->get($spout);

        if ($this->view->spout === null) {
            $this->view->error('invalid spout type given');
        }

        if (count($this->view->spout->params) > 0) {
            $this->view->idAttr = 'new-' . rand();
            echo $this->view->render('src/templates/source_params.phtml');
        }
    }

    /**
     * return all Sources suitable for navigation panel
     * html
     *
     * @param array $sources sources to render
     *
     * @return string htmltext
     */
    public function renderSources(array $sources) {
        $html = '';
        foreach ($sources as $source) {
            $this->view->source = $source['title'];
            $this->view->sourceid = $source['id'];
            $this->view->unread = $source['unread'];
            $html .= $this->view->render('src/templates/source-nav.phtml');
        }

        return $html;
    }

    /**
     * load all available sources and return all Sources suitable
     * for navigation panel
     * html
     *
     * @return string htmltext
     */
    public function sourcesListAsString() {
        $sourcesDao = new \daos\Sources();
        $sources = $sourcesDao->getWithUnread();

        return $this->renderSources($sources);
    }

    /**
     * render spouts params
     * json
     *
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function write(Base $f3, array $params) {
        $f3->get('auth')->needsLoggedIn();

        $sourcesDao = new \daos\Sources();

        // read data
        $headers = \F3::get('HEADERS');
        $body = \F3::get('BODY');
        if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'application/json') === 0) {
            $data = json_decode($body, true);
        } else {
            parse_str($body, $data);
        }

        $data['spout'] = str_replace('_', '\\', $data['spout']);
        if (!isset($data['title']) || strlen(trim($data['title'])) === 0) {
            // try to fetch title, if it is not filled in
            $loader = new \helpers\ContentLoader();
            $title = $loader->fetchTitle($data);

            if ($title) {
                $data['title'] = $title;
            } else {
                $this->view->jsonError(['title' => 'no title given and could not fetch it']);
            }
        }

        if (!isset($data['spout'])) {
            $this->view->jsonError(['spout' => 'no data for spout given']);
        }

        // clean up title and tag data to prevent XSS
        $title = htmlspecialchars($data['title']);
        $tags = array_map('htmlspecialchars', $data['tags']);
        $spout = $data['spout'];
        $filter = $data['filter'];

        unset($data['title']);
        unset($data['spout']);
        unset($data['filter']);
        unset($data['tags']);

        // check if source already exists
        $id = $params['id'];
        $sourceExists = $sourcesDao->isValid('id', $id);

        // load password value if not changed for spouts containing passwords
        if ($sourceExists) {
            $spoutLoader = new \helpers\SpoutLoader();
            $spoutInstance = $spoutLoader->get($spout);

            foreach ($spoutInstance->params as $spoutParamName => $spoutParam) {
                if ($spoutParam['type'] === 'password'
                    && empty($data[$spoutParamName])) {
                    if (!isset($oldSource)) {
                        $oldSource = $sourcesDao->get($id);
                        $oldParams = json_decode(html_entity_decode($oldSource['params']), true);
                    }
                    $data[$spoutParamName] = $oldParams[$spoutParamName];
                }
            }
        }

        $validation = $sourcesDao->validate($title, $spout, $data);
        if ($validation !== true) {
            $this->view->error(json_encode($validation));
        }

        // add/edit source
        if (!$sourceExists) {
            $id = $sourcesDao->add($title, $tags, $filter, $spout, $data);
        } else {
            $sourcesDao->edit($id, $title, $tags, $filter, $spout, $data);
        }

        // autocolor tags
        $tagsDao = new \daos\Tags();
        foreach ($tags as $tag) {
            $tagsDao->autocolorTag(trim($tag));
        }

        // cleanup tags
        $tagsDao->cleanup($sourcesDao->getAllTags());

        $return = [
            'success' => true,
            'id' => (int) $id,
            'title' => $title
        ];

        // only for selfoss ui (update stats in navigation)
        if ($f3->ajax()) {
            // get new tag list with updated count values
            $tagController = new \controllers\Tags($this->authentication, $this->view);
            $return['tags'] = $tagController->tagsListAsString();

            // get new sources list
            $sourcesController = new \controllers\Sources($this->authentication, $this->view);
            $return['sources'] = $sourcesController->sourcesListAsString();
        }

        $this->view->jsonSuccess($return);
    }

    /**
     * return source stats in HTML for nav update
     * json
     *
     * @return void
     */
    public function sourcesStats() {
        $this->authentication->needsLoggedInOrPublicMode();

        $this->view->jsonSuccess([
            'success' => true,
            'sources' => $this->sourcesListAsString()
        ]);
    }

    /**
     * delete source
     * json
     *
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function remove(Base $f3, array $params) {
        $f3->get('auth')->needsLoggedIn();

        $id = $params['id'];

        $sourceDao = new \daos\Sources();

        if (!$sourceDao->isValid('id', $id)) {
            $this->view->error('invalid id given');
        }

        $sourceDao->delete($id);

        // cleanup tags
        $tagsDao = new \daos\Tags();
        $allTags = $sourceDao->getAllTags();
        $tagsDao->cleanup($allTags);

        $this->view->jsonSuccess([
            'success' => true
        ]);
    }

    /**
     * update source
     * text
     *
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function update(Base $f3, array $params) {
        $id = $params['id'];

        // only allow access for localhost and authenticated users
        if (!$f3->get('auth')->allowedToUpdate()) {
            die('unallowed access');
        }

        // update the feed
        $loader = new \helpers\ContentLoader();
        $loader->updateSingle($id);
        echo 'finished';
    }

    /**
     * returns all available sources
     * json
     *
     * @return void
     */
    public function listSources() {
        $this->authentication->needsLoggedIn();

        // load sources
        $sourcesDao = new \daos\Sources();
        $sources = $sourcesDao->getWithIcon();

        // get last icon
        foreach ($sources as &$source) {
            $source['params'] = json_decode(html_entity_decode($source['params']), true);
            $source['error'] = $source['error'] === null ? '' : $source['error'];
        }

        $this->view->jsonSuccess($sources);
    }

    /**
     * returns all available spouts
     * json
     *
     * @return void
     */
    public function spouts() {
        $this->authentication->needsLoggedIn();

        $spoutLoader = new \helpers\SpoutLoader();
        $spouts = $spoutLoader->all();
        $this->view->jsonSuccess($spouts);
    }

    /**
     * returns all sources with unread items
     * json
     *
     * @return void
     */
    public function stats() {
        $this->authentication->needsLoggedInOrPublicMode();

        // load sources
        $sourcesDao = new \daos\Sources();
        $sources = $sourcesDao->getWithUnread();

        $this->view->jsonSuccess($sources);
    }
}
