<?php

namespace daos;

use DateTime;

/**
 * Interface describing concrete DAO for working with items.
 */
interface ItemsInterface {
    /**
     * mark item as read
     *
     * @param int $id
     *
     * @return void
     */
    public function mark($id);

    /**
     * mark item as unread
     *
     * @param int $id
     *
     * @return void
     */
    public function unmark($id);

    /**
     * starr item
     *
     * @param int $id the item
     *
     * @return void
     */
    public function starr($id);

    /**
     * unstarr item
     *
     * @param int $id the item
     *
     * @return void
     */
    public function unstarr($id);

    /**
     * add new item
     *
     * @param array $values
     *
     * @return void
     */
    public function add($values);

    /**
     * checks whether an item with given
     * uid exists or not
     *
     * @param string $uid
     *
     * @return bool
     */
    public function exists($uid);

    /**
     * search whether given uids are already in database or not
     *
     * @param array $itemsInFeed list with ids for checking whether they are already in database or not
     * @param int $sourceId the id of the source to search for the items
     *
     * @return array with all existing uids from itemsInFeed (array (uid => id););
     */
    public function findAll($itemsInFeed, $sourceId);

    /**
     * Update the time items were last seen in the feed to prevent unwanted cleanup.
     *
     * @param int[] $itemIds ids of items to update
     *
     * @return void
     */
    public function updateLastSeen(array $itemIds);

    /**
     * cleanup orphaned and old items
     *
     * @param ?DateTime $date date to delete all items older than this value
     *
     * @return void
     */
    public function cleanup(DateTime $date = null);

    /**
     * returns items
     *
     * @param mixed $options search, offset and filter params
     *
     * @return mixed items as array
     */
    public function get($options = []);

    /**
     * returns whether more items for last given
     * get call are available
     *
     * @return bool
     */
    public function hasMore();

    /**
     * Obtain new or changed items in the database for synchronization with clients.
     *
     * @param int $sinceId id of last seen item
     * @param DateTime $notBefore cut off time stamp
     * @param DateTime $since timestamp of last seen item
     * @param int $howMany
     *
     * @return array of items
     */
    public function sync($sinceId, DateTime $notBefore, DateTime $since, $howMany);

    /**
     * Lowest id of interest
     *
     * @return int lowest id of interest
     */
    public function lowestIdOfInterest();

    /**
     * Last id in db
     *
     * @return int last id in db
     */
    public function lastId();

    /**
     * return all thumbnails
     *
     * @return string[] array with thumbnails
     */
    public function getThumbnails();

    /**
     * return all icons
     *
     * @return string[] array with all icons
     */
    public function getIcons();

    /**
     * return all thumbnails
     *
     * @param string $thumbnail name
     *
     * @return bool true if thumbnail is still in use
     */
    public function hasThumbnail($thumbnail);

    /**
     * return all icons
     *
     * @param string $icon file
     *
     * @return bool true if icon is still in use
     */
    public function hasIcon($icon);

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
     * returns the icon of the last fetched item.
     *
     * @param int $sourceid id of the source
     *
     * @return ?string
     */
    public function getLastIcon($sourceid);

    /**
     * returns the amount of entries in database which are unread
     *
     * @return int amount of entries in database which are unread
     */
    public function numberOfUnread();

    /**
     * returns the amount of total, unread, starred entries in database
     *
     * @return array mount of total, unread, starred entries in database
     */
    public function stats();

    /**
     * returns the datetime of the last item update or user action in db
     *
     * @return string timestamp
     */
    public function lastUpdate();

    /**
     * returns the statuses of items last update
     *
     * @param DateTime $since minimal date of returned items
     *
     * @return array of unread, starred, etc. status of specified items
     */
    public function statuses(DateTime $since);

    /**
     * bulk update of item status
     *
     * @param array $statuses array of statuses updates
     *
     * @return void
     */
    public function bulkStatusUpdate(array $statuses);
}
