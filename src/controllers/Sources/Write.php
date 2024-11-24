<?php

declare(strict_types=1);

namespace controllers\Sources;

use helpers\Authentication;
use helpers\ContentLoader;
use helpers\Filters\FilterFactory;
use helpers\Filters\FilterSyntaxError;
use helpers\Misc;
use helpers\Request;
use helpers\SpoutLoader;
use helpers\View;
use spouts\Parameter;

/**
 * Controller for creating and editing sources
 */
class Write {
    private Authentication $authentication;
    private ContentLoader $contentLoader;
    private Request $request;
    private \daos\Sources $sourcesDao;
    private SpoutLoader $spoutLoader;
    private \daos\Tags $tagsDao;
    private View $view;

    public function __construct(
        Authentication $authentication,
        ContentLoader $contentLoader,
        Request $request,
        \daos\Sources $sourcesDao,
        SpoutLoader $spoutLoader,
        \daos\Tags $tagsDao,
        View $view
    ) {
        $this->authentication = $authentication;
        $this->contentLoader = $contentLoader;
        $this->request = $request;
        $this->sourcesDao = $sourcesDao;
        $this->spoutLoader = $spoutLoader;
        $this->tagsDao = $tagsDao;
        $this->view = $view;
    }

    /**
     * Update source data or create a new source.
     * json
     *
     * @param ?string $id ID of source to update, or null to create a new one
     */
    public function write(?string $id = null): void {
        $this->authentication->ensureIsPrivileged();

        $data = $this->request->getData();

        if (!is_array($data)) {
            $this->view->jsonError([
                'error' => 'The request body needs to contain a dictionary/object.',
            ]);
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
        $filter = $data['filter'];

        try {
            // Try to create a filter object for validation.
            FilterFactory::fromString($filter ?? '');
        } catch (FilterSyntaxError $exception) {
            $this->view->jsonError(['filter' => $exception->getMessage()]);
        }

        unset($data['title']);
        unset($data['spout']);
        unset($data['filter']);
        unset($data['tags']);

        // We assume numeric id means source already exists, new sources will have “new-” prefix.
        try {
            if ($id !== null) {
                $id = Misc::forceId($id);
            }
        } catch (\InvalidArgumentException $e) {
            $id = null;
        }

        // load password value if not changed for spouts containing passwords
        $oldParams = null;
        if ($id !== null) {
            $spoutInstance = $this->spoutLoader->get($spout);
            if ($spoutInstance === null) {
                $this->view->jsonError([
                    'spout' => 'spout does not exist',
                ]);
            }

            foreach ($spoutInstance->params as $spoutParamName => $spoutParam) {
                if ($spoutParam['type'] === Parameter::TYPE_PASSWORD && empty($data[$spoutParamName])) {
                    if ($oldParams === null) {
                        $oldSource = $this->sourcesDao->get($id);
                        if ($oldSource === null) {
                            $oldParams = [];
                        } else {
                            $oldParams = json_decode(html_entity_decode($oldSource['params']), true);
                        }
                    }
                    $data[$spoutParamName] = $oldParams[$spoutParamName] ?? '';
                }
            }
        }

        $validation = $this->sourcesDao->validate($title, $spout, $data);
        if ($validation !== true) {
            $this->view->jsonError($validation);
        }

        // add/edit source
        if ($id === null) {
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
