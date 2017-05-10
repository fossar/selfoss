<?php

namespace controllers;

use Base;
use helpers\Authentication;
use helpers\SpoutLoader;
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

    /** @var \daos\Sources sources */
    private $sourcesDao;

    /** @var SpoutLoader spout loader */
    private $spoutLoader;

    /** @var \daos\Tags tags */
    private $tagsDao;

    /** @var View view helper */
    private $view;

    public function __construct(Authentication $authentication, \daos\Sources $sourcesDao, SpoutLoader $spoutLoader, \daos\Tags $tagsDao, View $view) {
        $this->authentication = $authentication;
        $this->sourcesDao = $sourcesDao;
        $this->spoutLoader = $spoutLoader;
        $this->tagsDao = $tagsDao;
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
        $this->view->spouts = $this->spoutLoader->all();

        // load sources
        echo '<button class="source-add">' . \F3::get('lang_source_add') . '</button>' .
             '<a class="source-export" href="opmlexport">' . \F3::get('lang_source_export') . '</a>' .
             '<a class="source-opml" href="opml">' . \F3::get('lang_source_opml');
        $sourcesHtml = '</a>';

        foreach ($this->sourcesDao->getWithIcon() as $source) {
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

        $this->view->spouts = $this->spoutLoader->all();
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

        $spout = str_replace('_', '\\', $_GET['spout']);
        $this->view->spout = $this->spoutLoader->get($spout);

        if ($this->view->spout === null) {
            $this->view->error('invalid spout type given');
        }

        if (count($this->view->spout->params) > 0) {
            $this->view->idAttr = 'new-' . rand();
            echo $this->view->render('src/templates/source_params.phtml');
        }
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
        $this->authentication->needsLoggedIn();

        $id = $params['id'];

        if (!$this->sourcesDao->isValid('id', $id)) {
            $this->view->error('invalid id given');
        }

        $this->sourcesDao->delete($id);

        // cleanup tags
        $allTags = $this->sourcesDao->getAllTags();
        $this->tagsDao->cleanup($allTags);

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
        $sources = $this->sourcesDao->getWithIcon();

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

        $spouts = $this->spoutLoader->all();
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
        $sources = $this->sourcesDao->getWithUnread();

        $this->view->jsonSuccess($sources);
    }
}
