<?php

declare(strict_types=1);

namespace daos;

use DateTime;
use DateTimeImmutable;

/**
 * Interface describing concrete DAO for working with items.
 */
interface ItemsInterface {
    /**
     * Mark items as read.
     *
     * @param int[] $ids
     */
    public function mark(array $ids): void;

    /**
     * Mark items as unread.
     *
     * @param int[] $ids
     */
    public function unmark(array $ids): void;

    /**
     * starr item
     *
     * @param int $id the item
     */
    public function starr(int $id): void;

    /**
     * unstarr item
     *
     * @param int $id the item
     */
    public function unstarr(int $id): void;

    /**
     * add new item
     */
    public function add(array $values): void;

    /**
     * checks whether an item with given
     * uid exists or not
     */
    public function exists(string $uid): bool;

    /**
     * search whether given uids are already in database or not
     *
     * @param array $itemsInFeed list with ids for checking whether they are already in database or not
     * @param int $sourceId the id of the source to search for the items
     *
     * @return array with all existing uids from itemsInFeed (array (uid => id););
     */
    public function findAll(array $itemsInFeed, int $sourceId): array;

    /**
     * Update the time items were last seen in the feed to prevent unwanted cleanup.
     *
     * @param int[] $itemIds ids of items to update
     */
    public function updateLastSeen(array $itemIds): void;

    /**
     * cleanup orphaned and old items
     *
     * @param ?DateTime $date date to delete all items older than this value
     */
    public function cleanup(DateTime $date = null): void;

    /**
     * returns items
     *
     * @param ItemOptions $options search, offset and filter params
     *
     * @return array<array<mixed>> items as array
     */
    public function get(ItemOptions $options): array;

    /**
     * returns whether more items for last given
     * get call are available
     */
    public function hasMore(): bool;

    /**
     * Obtain new or changed items in the database for synchronization with clients.
     *
     * @param int $sinceId id of last seen item
     * @param DateTime $notBefore cut off time stamp
     * @param DateTime $since timestamp of last seen item
     *
     * @return array of items
     */
    public function sync(int $sinceId, DateTime $notBefore, DateTime $since, int $howMany): array;

    /**
     * Lowest id of interest
     *
     * @return int lowest id of interest
     */
    public function lowestIdOfInterest(): int;

    /**
     * Last id in db
     *
     * @return int last id in db
     */
    public function lastId(): int;

    /**
     * return all thumbnails
     *
     * @return string[] array with thumbnails
     */
    public function getThumbnails(): array;

    /**
     * return all icons
     *
     * @return string[] array with all icons
     */
    public function getIcons(): array;

    /**
     * return all thumbnails
     *
     * @param string $thumbnail name
     *
     * @return bool true if thumbnail is still in use
     */
    public function hasThumbnail(string $thumbnail): bool;

    /**
     * return all icons
     *
     * @param string $icon file
     *
     * @return bool true if icon is still in use
     */
    public function hasIcon(string $icon): bool;

    /**
     * returns the amount of entries in database which are unread
     *
     * @return int amount of entries in database which are unread
     */
    public function numberOfUnread(): int;

    /**
     * returns the amount of total, unread, starred entries in database
     *
     * @return array mount of total, unread, starred entries in database
     */
    public function stats(): array;

    /**
     * returns the datetime of the last item update or user action in db
     */
    public function lastUpdate(): ?DateTimeImmutable;

    /**
     * returns the statuses of items last update
     *
     * @param DateTime $since minimal date of returned items
     *
     * @return array of unread, starred, etc. status of specified items
     */
    public function statuses(DateTime $since): array;

    /**
     * bulk update of item status
     *
     * @param array $statuses array of statuses updates
     */
    public function bulkStatusUpdate(array $statuses): void;
}
