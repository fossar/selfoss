<?php

declare(strict_types=1);

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
     * @return array<array{tag: string, color: string}>
     */
    public function get(): array;

    /**
     * returns all tags with color and unread count
     *
     * @return array<array{tag: string, color: string, unread: int}>
     */
    public function getWithUnread(): array;

    /**
     * remove all unused tag color definitions
     *
     * @param string[] $tags available tags
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

    /**
     * returns raw tags table contents
     *
     * @return array<array{tag: string, color: string}> of all tags
     */
    public function getRaw(): array;

    /**
     * inserts raw data into tags table
     *
     * @param array<array{tag: string, color: string}> $tags
     */
    public function insertRaw(array $tags): void;
}
