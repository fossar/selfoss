<?php

declare(strict_types=1);

namespace daos;

/**
 * Interface describing concrete DAO for working with sources.
 */
interface SourcesInterface {
    /**
     * add new source
     *
     * @param string[] $tags
     * @param string $spout the source type
     * @param array $params depends from spout
     *
     * @return int new id
     */
    public function add(string $title, array $tags, ?string $filter, string $spout, array $params): int;

    /**
     * edit source
     *
     * @param int $id the source id
     * @param string $title new title
     * @param string[] $tags new tags
     * @param string $spout new spout
     * @param array $params the new params
     */
    public function edit(int $id, string $title, array $tags, ?string $filter, string $spout, array $params): void;

    /**
     * delete source
     */
    public function delete(int $id): void;

    /**
     * save error message
     *
     * @param int $id the source id
     * @param string $error error message
     */
    public function error(int $id, string $error): void;

    /**
     * sets the last updated timestamp
     *
     * @param int $id the source id
     * @param ?int $lastEntry timestamp of the newest item or NULL when no items were added
     */
    public function saveLastUpdate(int $id, ?int $lastEntry): void;

    /**
     * returns all sources
     *
     * @return mixed all sources
     */
    public function getByLastUpdate();

    /**
     * returns specified source (null if it doesnt exist)
     * or all sources if no id specified
     *
     * @param ?int $id specification of source id
     *
     * @return ?mixed specified source or all sources
     */
    public function get(?int $id = null);

    /**
     * returns all sources including unread count
     *
     * @return mixed all sources
     */
    public function getWithUnread();

    /**
     * returns all sources including last icon
     *
     * @return mixed all sources
     */
    public function getWithIcon();

    /**
     * returns all tags
     *
     * @return mixed all sources
     */
    public function getAllTags();

    /**
     * returns tags of a source
     *
     * @return mixed tags of a source
     */
    public function getTags(int $id);

    /**
     * test if a source is already present using title, spout and params.
     * if present returns the id, else returns 0
     *
     * @param  string  $spout the source type
     * @param  array   $params depends from spout
     *
     * @return int id if any record is found
     */
    public function checkIfExists(string $title, string $spout, array $params): int;
}
