<?php

namespace controllers\Sources;

use helpers\Authentication;
use helpers\ContentLoader;
use helpers\SpoutLoader;
use helpers\View;

/**
 * Controller for creating and editing sources
 */
class Write {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var ContentLoader content loader */
    private $contentLoader;

    /** @var \daos\Sources sources */
    private $sourcesDao;

    /** @var SpoutLoader spout loader */
    private $spoutLoader;

    /** @var \daos\Tags tags */
    private $tagsDao;

    /** @var View view helper */
    private $view;

    public function __construct(Authentication $authentication, ContentLoader $contentLoader, \daos\Sources $sourcesDao, SpoutLoader $spoutLoader, \daos\Tags $tagsDao, View $view) {
        $this->authentication = $authentication;
        $this->contentLoader = $contentLoader;
        $this->sourcesDao = $sourcesDao;
        $this->spoutLoader = $spoutLoader;
        $this->tagsDao = $tagsDao;
        $this->view = $view;
    }

    /**
     * Update source data or create a new source.
     * json
     *
     * @param ?int $id ID of source to update, or null to create a new one
     *
     * @return void
     */
    public function write($id = null) {
        $this->authentication->needsLoggedIn();

        // read data
        $body = file_get_contents('php://input');
        $contentType = $_SERVER['CONTENT_TYPE'] ?: '';
        if (strpos($contentType, 'application/json') === 0) {
            $data = json_decode($body, true);
        } else {
            parse_str($body, $data);
        }

        if (empty($data['spout'])) {
            $this->view->jsonError(['spout' => 'spout not selected']);
        }

        $data['spout'] = str_replace('_', '\\', $data['spout']);
        if (!isset($data['title']) || strlen(trim($data['title'])) === 0) {
            // try to fetch title, if it is not filled in
            $title = $this->contentLoader->fetchTitle($data);

            if ($title) {
                $data['title'] = $title;
            } else {
                $this->view->jsonError(['title' => 'no title given and could not fetch it']);
            }
        }

        // clean up title and tag data to prevent XSS
        $title = htmlspecialchars($data['title']);
        if (!isset($data['tags'])) {
            $data['tags'] = [];
        }
        $tags = array_map('htmlspecialchars', $data['tags']);
        $spout = $data['spout'];
        $filter = isset($data['filter']) ? $data['filter'] : null;

        unset($data['title']);
        unset($data['spout']);
        unset($data['filter']);
        unset($data['tags']);

        // check if source already exists
        $sourceExists = $id !== null && $this->sourcesDao->isValid('id', $id);

        // load password value if not changed for spouts containing passwords
        if ($sourceExists) {
            $spoutInstance = $this->spoutLoader->get($spout);

            foreach ($spoutInstance->params as $spoutParamName => $spoutParam) {
                if ($spoutParam['type'] === 'password'
                    && empty($data[$spoutParamName])) {
                    if (!isset($oldSource)) {
                        $oldSource = $this->sourcesDao->get($id);
                        $oldParams = json_decode(html_entity_decode($oldSource['params']), true);
                    }
                    $data[$spoutParamName] = isset($oldParams[$spoutParamName]) ? $oldParams[$spoutParamName] : '';
                }
            }
        }

        $validation = $this->sourcesDao->validate($title, $spout, $data);
        if ($validation !== true) {
            $this->view->jsonError($validation);
        }

        // add/edit source
        if (!$sourceExists) {
            $id = $this->sourcesDao->add($title, $tags, $filter, $spout, $data);
        } else {
            $this->sourcesDao->edit($id, $title, $tags, $filter, $spout, $data);
        }

        // autocolor tags
        foreach ($tags as $tag) {
            $this->tagsDao->autocolorTag(trim($tag));
        }

        // cleanup tags
        $this->tagsDao->cleanup($this->sourcesDao->getAllTags());

        $return = [
            'success' => true,
            'id' => (int) $id,
            'title' => $title,
        ];

        // only for selfoss ui (update stats in navigation)
        if ($this->view->isAjax()) {
            // get new tag list with updated count values
            $return['tags'] = $this->tagsDao->getWithUnread();

            // get new sources list
            $return['sources'] = $this->sourcesDao->getWithUnread();
        }

        $this->view->jsonSuccess($return);
    }
}
