<?php

namespace daos;

/**
 * Interface describing concrete DAO for working with tags.
 */
interface TagsInterface {
    /**
     * save given tag color
     *
     * @param string $tag
     * @param string $color
     *
     * @return void
     */
    public function saveTagColor($tag, $color);

    /**
     * save given tag with random color
     *
     * @param string $tag
     *
     * @return void
     */
    public function autocolorTag($tag);

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
     *
     * @return void
     */
    public function cleanup(array $tags);

    /**
     * check whether tag color is defined.
     *
     * @param string $tag
     *
     * @return bool true if color is used by an tag
     */
    public function hasTag($tag);

    /**
     * delete tag
     *
     * @param string $tag
     *
     * @return void
     */
    public function delete($tag);
}
