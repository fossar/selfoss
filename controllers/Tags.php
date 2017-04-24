<?php

namespace controllers;

/**
 * Controller for tag access
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Tags extends BaseController {
    /**
     * returns all tags
     * html
     *
     * @return void
     */
    public function tagslist() {
        $this->needsLoggedInOrPublicMode();

        echo $this->tagsListAsString();
    }

    /**
     * returns all tags
     * html
     *
     * @return string
     */
    public function tagsListAsString() {
        $tagsDao = new \daos\Tags();

        return $this->renderTags($tagsDao->getWithUnread());
    }

    /**
     * returns all tags
     * html
     *
     * @param array $tags
     *
     * @return string
     */
    public function renderTags(array $tags) {
        $html = '';
        foreach ($tags as $tag) {
            $this->view->tag = $tag['tag'];
            $this->view->color = $tag['color'];
            $this->view->unread = $tag['unread'];
            $html .= $this->view->render('templates/tag.phtml');
        }

        return $html;
    }

    /* @var array cache of tags and associated colors */
    protected $tagsColors = null;

    /**
     * returns item tags as HTML
     *
     * @param array $itemTags tags for this item
     * @param array $tags list of all the tags and their color
     *
     * @return string
     */
    public function tagsAddColors(array $itemTags, array $tags = null) {
        if ($tags === null) {
            if ($this->tagsColors === null) {
                $tagsDao = new \daos\Tags();
                $this->tagsColors = $this->getTagsWithColors($tagsDao->get());
            }
        } else {
            $this->tagsColors = $this->getTagsWithColors($tags);
        }

        // assign tag colors
        $itemTagsWithColors = [];
        foreach ($itemTags as $tag) {
            $tag = trim($tag);
            if (strlen($tag) > 0 && isset($this->tagsColors[$tag])) {
                $itemTagsWithColors[$tag] = $this->tagsColors[$tag];
            }
        }

        return $itemTagsWithColors;
    }

    /**
     * set tag color
     *
     * @return void
     */
    public function color() {
        $this->needsLoggedIn();

        // read data
        parse_str(\F3::get('BODY'), $data);

        $tag = $data['tag'];
        $color = $data['color'];

        if (!isset($tag) || strlen(trim($tag)) === 0) {
            $this->view->error('invalid or no tag given');
        }
        if (!isset($color) || strlen(trim($color)) === 0) {
            $this->view->error('invalid or no color given');
        }

        $tagsDao = new \daos\Tags();
        $tagsDao->saveTagColor($tag, $color);
        $this->view->jsonSuccess([
            'success' => true
        ]);
    }

    /**
     * returns all tags
     * html
     *
     * @return void
     */
    public function listTags() {
        $this->needsLoggedInOrPublicMode();

        $tagsDao = new \daos\Tags();
        $tags = $tagsDao->getWithUnread();

        $this->view->jsonSuccess($tags);
    }

    /**
     * return tag => color array
     *
     * @param array $tags
     *
     * @return array tag color array
     */
    private function getTagsWithColors(array $tags) {
        $assocTags = [];
        foreach ($tags as $tag) {
            $assocTags[$tag['tag']]['backColor'] = $tag['color'];
            $assocTags[$tag['tag']]['foreColor'] = \helpers\Color::colorByBrightness($tag['color']);
        }

        return $assocTags;
    }
}
