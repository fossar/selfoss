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
     * @param array<string, mixed> $params spout-specific parameters
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
     * @param array<string, mixed> $params the new params
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
     * Gets the number of sources.
     */
    public function count(): int;

    /**
     * returns all sources
     *
     * @return array<array{id: int, title: string, tags: string, spout: string, params: string, filter: ?string, error: ?string, lastupdate: ?int, lastentry: ?int}> all sources
     */
    public function getByLastUpdate(): array;

    /**
     * Returns source with given id (or null if it doesnt exist).
     *
     * @return ?array{id: int, title: string, tags: string, spout: string, params: string, filter: ?string, error: ?string, lastupdate: ?int, lastentry: ?int}
     */
    public function get(int $id): ?array;

    /**
     * Returns specified source all sources.
     *
     * @return array<array{id: int, title: string, tags: string[], spout: string, params: string, filter: ?string, error: ?string, lastupdate: ?int, lastentry: ?int}>
     */
    public function getAll(): array;

    /**
     * returns all sources including unread count
     *
     * @return array<array{id: int, title: string, unread: int}> all sources
     */
    public function getWithUnread(): array;

    /**
     * returns all sources including last icon
     *
     * @return array<array{id: int, title: string, tags: string[], spout: string, params: string, filter: ?string, error: ?string, lastentry: ?int, icon: ?string}> all sources
     */
    public function getWithIcon(): array;

    /**
     * returns all tags
     *
     * @return string[] all sources
     */
    public function getAllTags(): array;

    /**
     * returns tags of a source
     *
     * @return string[] tags of a source
     */
    public function getTags(int $id): array;

    /**
     * test if a source is already present using title, spout and params.
     * if present returns the id, else returns 0
     *
     * @param string $spout the source type
     * @param array<string, mixed> $params spout-specific parameters
     *
     * @return int id if any record is found
     */
    public function checkIfExists(string $title, string $spout, array $params): int;

    /**
     * returns raw sources table contents
     *
     * @return array<array{error: ?string, filter: string, id: int, lastentry: string, lastupdate: string, params: string, spout: string, tags: string[], title: string}> of all sources
     */
    public function getRaw(): array;

    /**
     * inserts raw data into sources table
     *
     * @param array<array{error: ?string, filter: string, id: int, lastentry: string, lastupdate: string, params: string, spout: string, tags: string[], title: string}> $sources
     */
    public function insertRaw(array $sources): void;
}
