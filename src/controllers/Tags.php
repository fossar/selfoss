<?php

declare(strict_types=1);

namespace controllers;

use helpers\Authentication;
use helpers\Request;
use helpers\StringKeyedArray;
use helpers\View;

/**
 * Controller for tag access
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Tags {
    /** @var ?StringKeyedArray<array{backColor: string, foreColor: string}> cache of tags and associated colors */
    protected ?StringKeyedArray $tagsColors = null;

    private Authentication $authentication;
    private Request $request;
    private \daos\Tags $tagsDao;
    private View $view;

    public function __construct(
        Authentication $authentication,
        Request $request,
        \daos\Tags $tagsDao,
        View $view
    ) {
        $this->authentication = $authentication;
        $this->request = $request;
        $this->tagsDao = $tagsDao;
        $this->view = $view;
    }

    /**
     * returns item tags as HTML
     *
     * @param string[] $itemTags tags for this item
     * @param ?array<array{tag: string, color: string}> $tags list of all the tags and their color
     *
     * @return StringKeyedArray<array{backColor: string, foreColor: string}>
     */
    public function tagsAddColors(array $itemTags, ?array $tags = null): StringKeyedArray {
        if ($tags === null) {
            if ($this->tagsColors === null) {
                $this->tagsColors = $this->getTagsWithColors($this->tagsDao->get());
            }
        } else {
            $this->tagsColors = $this->getTagsWithColors($tags);
        }

        /** @var StringKeyedArray<array{backColor: string, foreColor: string}> Tags with their associated colors */
        $itemTagsWithColors = new StringKeyedArray();
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
     */
    public function color(): void {
        $this->authentication->ensureIsPrivileged();

        $data = $this->request->getData();

        if (!is_array($data)) {
            $this->view->jsonError([
                'error' => 'The request body needs to contain a dictionary/object.',
            ]);
        }

        $tag = isset($data['tag']) && ($trimmed = trim($data['tag'])) !== '' ? $trimmed : null;
        $color = isset($data['color']) && ($trimmed = trim($data['color'])) !== '' ? $trimmed : null;

        if ($tag === null) {
            $this->view->error('invalid or no tag given');
        }
        if ($color === null) {
            $this->view->error('invalid or no color given');
        }

        $this->tagsDao->saveTagColor($tag, $color);
        $this->view->jsonSuccess([
            'success' => true,
        ]);
    }

    /**
     * returns all tags
     * html
     */
    public function listTags(): void {
        $this->authentication->ensureCanRead();

        $tags = $this->tagsDao->getWithUnread();

        $this->view->jsonSuccess($tags);
    }

    /**
     * Returns an associative array of tags with their foreground and background colors.
     *
     * @param array<array{tag: string, color: string}> $tags tags to colorize
     *
     * @return StringKeyedArray<array{backColor: string, foreColor: string}> tag color array
     */
    private function getTagsWithColors(array $tags): StringKeyedArray {
        /** @var StringKeyedArray<array{backColor: string, foreColor: string}> */
        $assocTags = new StringKeyedArray();
        foreach ($tags as $tag) {
            $assocTags[$tag['tag']] = [
                'backColor' => $tag['color'],
                'foreColor' => \helpers\Color::colorByBrightness($tag['color']),
            ];
        }

        return $assocTags;
    }
}
