<?php

namespace controllers;

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
     * json
     *
     * @return void
     */
    public function show() {
        $this->authentication->needsLoggedIn();

        // get available spouts
        $spouts = $this->spoutLoader->all();
        $sources = [];

        // load sources
        foreach ($this->sourcesDao->getWithIcon() as $source) {
            // decode params
            $source['params'] = json_decode(html_entity_decode($source['params']), true);
            $sources[] = $source;
        }

        $this->view->jsonSuccess([
            'spouts' => $spouts,
            'sources' => $sources,
        ]);
    }

    /**
     * add new source
     * json
     *
     * @return void
     */
    public function add() {
        $this->authentication->needsLoggedIn();

        $spouts = $this->spoutLoader->all();

        $this->view->jsonSuccess([
            'spouts' => $spouts,
        ]);
    }

    /**
     * render spouts params
     * json
     *
     * @return void
     */
    public function params() {
        $this->authentication->needsLoggedIn();

        if (!isset($_GET['spout'])) {
            $this->view->error('no spout type given');
        }

        $spoutClass = str_replace('_', '\\', $_GET['spout']);
        $spout = $this->spoutLoader->get($spoutClass);

        if ($spout === null) {
            $this->view->error('invalid spout type given');
        }

        $id = 'new-' . rand();
        $this->view->jsonSuccess([
            'id' => $id,
            'spout' => $spout,
        ]);
    }

    /**
     * delete source
     * json
     *
     * @param int $id ID of source to remove
     *
     * @return void
     */
    public function remove($id) {
        $this->authentication->needsLoggedIn();

        if (!$this->sourcesDao->isValid('id', $id)) {
            $this->view->error('invalid id given');
        }

        $this->sourcesDao->delete($id);

        // cleanup tags
        $allTags = $this->sourcesDao->getAllTags();
        $this->tagsDao->cleanup($allTags);

        $this->view->jsonSuccess([
            'success' => true,
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
