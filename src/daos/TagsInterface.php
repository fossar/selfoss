<?php

namespace daos;

/**
 * Interface describing concrete DAO for working with tags.
 */
interface TagsInterface {
    /**
     * save given tag color
     */
    public function saveTagColor(string $tag, string $color): void;

    /**
     * save given tag with random color
     */
    public function autocolorTag(string $tag): void;

    /**
     * returns all tags with color
     *
     * @return array{tag: string, color: string}[]
     */
    public function get();

    /**
     * returns all tags with color and unread count
     *
     * @return array{tag: string, color: string, unread: int}[]
     */
    public function getWithUnread();

    /**
     * remove all unused tag color definitions
     *
     * @param array $tags available tags
     */
    public function cleanup(array $tags): void;

    /**
     * check whether tag color is defined.
     *
     * @return bool true if color is used by an tag
     */
    public function hasTag(string $tag): bool;

    /**
     * delete tag
     */
    public function delete(string $tag): void;
}
