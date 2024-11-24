<?php

declare(strict_types=1);

namespace controllers;

use helpers\Authentication;
use helpers\Misc;
use helpers\SpoutLoader;
use helpers\View;
use InvalidArgumentException;

/**
 * Controller for sources handling
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Sources {
    private Authentication $authentication;
    private \daos\Sources $sourcesDao;
    private SpoutLoader $spoutLoader;
    private \daos\Tags $tagsDao;
    private View $view;

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
     */
    public function show(): void {
        $this->authentication->ensureIsPrivileged();

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
     */
    public function add(): void {
        $this->authentication->ensureIsPrivileged();

        $spouts = $this->spoutLoader->all();

        $this->view->jsonSuccess([
            'spouts' => $spouts,
        ]);
    }

    /**
     * render spouts params
     * json
     */
    public function params(): void {
        $this->authentication->ensureIsPrivileged();

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
     * @param string $id ID of source to remove
     */
    public function remove(string $id): void {
        $this->authentication->ensureIsPrivileged();

        try {
            $id = Misc::forceId($id);
        } catch (InvalidArgumentException $e) {
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
     */
    public function listSources(): void {
        $this->authentication->ensureIsPrivileged();

        // load sources
        $sources = $this->sourcesDao->getWithIcon();

        // get last icon
        foreach ($sources as &$source) {
            $source['params'] = json_decode(html_entity_decode($source['params']), true);
            $source['error'] = $source['error'] ?? '';
        }

        $this->view->jsonSuccess($sources);
    }

    /**
     * returns all available spouts
     * json
     */
    public function spouts(): void {
        $this->authentication->ensureIsPrivileged();

        $spouts = $this->spoutLoader->all();
        $this->view->jsonSuccess($spouts);
    }

    /**
     * returns all sources with unread items
     * json
     */
    public function stats(): void {
        $this->authentication->ensureCanRead();

        // load sources
        $sources = $this->sourcesDao->getWithUnread();

        $this->view->jsonSuccess($sources);
    }
}
