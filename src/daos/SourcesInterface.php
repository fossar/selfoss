<?php

namespace daos;

/**
 * Interface describing concrete DAO for working with sources.
 */
interface SourcesInterface {
    /**
     * add new source
     *
     * @param string $title
     * @param string[] $tags
     * @param string $filter
     * @param string $spout the source type
     * @param array $params depends from spout
     *
     * @return int new id
     */
    public function add($title, array $tags, $filter, $spout, array $params);

    /**
     * edit source
     *
     * @param int $id the source id
     * @param string $title new title
     * @param string[] $tags new tags
     * @param string $filter
     * @param string $spout new spout
     * @param array $params the new params
     *
     * @return void
     */
    public function edit($id, $title, array $tags, $filter, $spout, array $params);

    /**
     * delete source
     *
     * @param int $id
     *
     * @return void
     */
    public function delete($id);

    /**
     * save error message
     *
     * @param int $id the source id
     * @param string $error error message
     *
     * @return void
     */
    public function error($id, $error);

    /**
     * sets the last updated timestamp
     *
     * @param int $id the source id
     * @param int $lastEntry timestamp of the newest item or NULL when no items were added
     *
     * @return void
     */
    public function saveLastUpdate($id, $lastEntry);

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
    public function get($id = null);

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
     * test if the value of a specified field is valid
     *
     * @param   string      $name
     * @param   mixed       $value
     *
     * @return  bool
     */
    public function isValid($name, $value);

    /**
     * returns all tags
     *
     * @return mixed all sources
     */
    public function getAllTags();

    /**
     * returns tags of a source
     *
     * @param int $id
     *
     * @return mixed tags of a source
     */
    public function getTags($id);

    /**
     * test if a source is already present using title, spout and params.
     * if present returns the id, else returns 0
     *
     * @param  string  $title
     * @param  string  $spout the source type
     * @param  array   $params depends from spout
     *
     * @return int id if any record is found
     */
    public function checkIfExists($title, $spout, array $params);
}
