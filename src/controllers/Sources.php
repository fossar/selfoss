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
