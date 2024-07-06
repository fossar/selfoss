<?php

declare(strict_types=1);

namespace daos\mysql;

use daos\DatabaseInterface;
use daos\ItemOptions;
use DateTime;
use DateTimeImmutable;
use helpers\Configuration;
use helpers\HtmlString;
use Monolog\Logger;

/**
 * Class for accessing persistent saved items -- mysql
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Harald Lapp <harald.lapp@gmail.com>
 */
class Items implements \daos\ItemsInterface {
    /** Indicates whether last run has more results or not */
    protected bool $hasMore = false;

    /** @var class-string SQL helper */
    protected static string $stmt = Statements::class;

    private Logger $logger;
    private Configuration $configuration;
    protected DatabaseInterface $database;

    public function __construct(Logger $logger, Configuration $configuration, DatabaseInterface $database) {
        $this->configuration = $configuration;
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * mark item as read
     *
     * @param non-empty-array<int> $ids
     */
    public function mark(array $ids): void {
        $ids = implode(
            ',',
            array_map(
                fn(int $id): string => (string) $id,
                $ids
            )
        );
        $this->database->exec(
            'UPDATE ' . $this->configuration->dbPrefix . "items SET unread=:unread WHERE id IN ($ids)",
            [
                'unread' => false,
            ]
        );
    }

    /**
     * mark item as unread
     *
     * @param non-empty-array<int> $ids
     */
    public function unmark(array $ids): void {
        $ids = implode(
            ',',
            array_map(
                fn(int $id): string => (string) $id,
                $ids
            )
        );
        $this->database->exec(
            'UPDATE ' . $this->configuration->dbPrefix . "items SET unread=:unread WHERE id IN ($ids)",
            [
                'unread' => true,
            ]
        );
    }

    /**
     * starr item
     *
     * @param int $id the item
     */
    public function starr(int $id): void {
        $this->database->exec('UPDATE ' . $this->configuration->dbPrefix . 'items SET starred=:bool WHERE id=:id', [
            ':bool' => true,
            ':id' => $id,
        ]);
    }

    /**
     * unstarr item
     *
     * @param int $id the item
     */
    public function unstarr(int $id): void {
        $this->database->exec('UPDATE ' . $this->configuration->dbPrefix . 'items SET starred=:bool WHERE id=:id', [
            ':bool' => false,
            ':id' => $id,
        ]);
    }

    /**
     * add new item
     *
     * @param array{datetime: \DateTimeInterface, title: HtmlString, content: HtmlString, thumbnail: ?string, icon: ?string, source: int, uid: string, link: string, author: ?string} $values
     */
    public function add(array $values): void {
        $this->database->exec(
            'INSERT INTO ' . $this->configuration->dbPrefix . 'items (
                datetime,
                title,
                content,
                unread,
                starred,
                source,
                thumbnail,
                icon,
                uid,
                link,
                author
            ) VALUES (
                :datetime,
                :title,
                :content,
                :unread,
                :starred,
                :source,
                :thumbnail,
                :icon,
                :uid,
                :link,
                :author
            )',
            [
                ':datetime' => $values['datetime']->format('Y-m-d H:i:s'),
                ':title' => $values['title']->getRaw(),
                ':content' => $values['content']->getRaw(),
                ':thumbnail' => $values['thumbnail'],
                ':icon' => $values['icon'],
                ':unread' => 1,
                ':starred' => 0,
                ':source' => $values['source'],
                ':uid' => $values['uid'],
                ':link' => $values['link'],
                ':author' => $values['author'],
            ]
        );
    }

    /**
     * checks whether an item with given
     * uid exists or not
     */
    public function exists(string $uid): bool {
        $res = $this->database->exec(
            'SELECT COUNT(*) AS amount FROM ' . $this->configuration->dbPrefix . 'items WHERE uid=:uid',
            [':uid' => [$uid, \PDO::PARAM_STR]]
        );

        return $res[0]['amount'] > 0;
    }

    /**
     * search whether given uids are already in database or not
     *
     * @param string[] $itemsInFeed list with ids for checking whether they are already in database or not
     * @param int $sourceId the id of the source to search for the items
     *
     * @return array<string, int> with all existing uids from itemsInFeed (array (uid => id))
     */
    public function findAll(array $itemsInFeed, int $sourceId): array {
        $itemsFound = [];
        if (count($itemsInFeed) < 1) {
            return $itemsFound;
        }

        $itemsInFeed = array_map(
            fn(string $uid): string => $this->database->quote($uid),
            $itemsInFeed
        );
        $query = 'SELECT id, uid AS uid FROM ' . $this->configuration->dbPrefix . 'items WHERE source = ' . $this->database->quote($sourceId) . ' AND uid IN (' . implode(',', $itemsInFeed) . ')';
        /** @var array<array{id: int, uid: string}> $result */
        $result = $this->database->exec($query);
        foreach ($result as $row) {
            $uid = $row['uid'];
            $itemsFound[$uid] = (int) $row['id'];
        }

        return $itemsFound;
    }

    /**
     * Update the time items were last seen in the feed to prevent unwanted cleanup.
     *
     * @param int[] $itemIds ids of items to update
     */
    public function updateLastSeen(array $itemIds): void {
        $this->database->exec('UPDATE ' . $this->configuration->dbPrefix . 'items SET lastseen = CURRENT_TIMESTAMP
            WHERE ' . static::$stmt::intRowMatches('id', $itemIds));
    }

    /**
     * cleanup orphaned and old items
     *
     * @param ?DateTime $date date to delete all items older than this value
     */
    public function cleanup(?DateTime $date): void {
        $this->database->exec('DELETE FROM ' . $this->configuration->dbPrefix . 'items
            WHERE source NOT IN (
                SELECT id FROM ' . $this->configuration->dbPrefix . 'sources)');
        if ($date !== null) {
            $this->database->exec(
                'DELETE FROM ' . $this->configuration->dbPrefix . 'items
                WHERE ' . static::$stmt::isFalse('starred') . ' AND lastseen<:date',
                [':date' => $date->format('Y-m-d') . ' 00:00:00']
            );
        }
    }

    /**
     * returns items
     *
     * @param ItemOptions $options search, offset and filter params
     *
     * @return array<array{id: int, datetime: DateTime, title: string, content: string, unread: bool, starred: bool, source: int, thumbnail: string, icon: string, uid: string, link: string, updatetime: DateTime, author: string, sourcetitle: string, tags: string[]}> items as array
     */
    public function get(ItemOptions $options): array {
        $params = [];
        $where = [static::$stmt::bool(true)];
        $order = 'DESC';
        $offset = $options->offset;

        // only starred
        if ($options->filter === 'starred') {
            $where[] = static::$stmt::isTrue('starred');
        }

        // only unread
        elseif ($options->filter === 'unread') {
            $where[] = static::$stmt::isTrue('unread');
            if ($this->configuration->unreadOrder === 'asc') {
                $order = 'ASC';
            }
        }

        // search
        if ($options->search !== null) {
            if (preg_match('#^/(?P<regex>.+)/$#', $options->search, $matches)) {
                $params[':search'] = $params[':search2'] = $params[':search3'] = [$matches['regex'], \PDO::PARAM_STR];
                $where[] = static::$stmt::exprOr(static::$stmt::matchesRegex('items.title', ':search'), static::$stmt::matchesRegex('items.content', ':search2'), static::$stmt::matchesRegex('sources.title', ':search3'));
            } else {
                $search = implode('%', \helpers\Search::splitTerms($options->search));
                $params[':search'] = $params[':search2'] = $params[':search3'] = ['%' . $search . '%', \PDO::PARAM_STR];
                $where[] = '(items.title LIKE :search OR items.content LIKE :search2 OR sources.title LIKE :search3) ';
            }
        }

        // tag filter
        if ($options->tag !== null) {
            $params[':tag'] = $options->tag;
            $where[] = 'items.source=sources.id';
            $where[] = static::$stmt::csvRowMatches('sources.tags', ':tag');
        }
        // source filter
        elseif ($options->source !== null) {
            $params[':source'] = [$options->source, \PDO::PARAM_INT];
            $where[] = 'items.source=:source ';
        }

        // update time filter
        if ($options->updatedSince !== null) {
            $params[':updatedsince'] = [
                static::$stmt::datetime($options->updatedSince), \PDO::PARAM_STR,
            ];
            $where[] = 'items.updatetime > :updatedsince ';
        }

        // seek pagination (alternative to offset)
        if ($options->fromDatetime !== null && $options->fromId !== null) {
            // discard offset as it makes no sense to mix offset pagination
            // with seek pagination.
            $offset = 0;

            $offset_from_datetime_sql = static::$stmt::datetime($options->fromDatetime);
            $params[':offset_from_datetime'] = [
                $offset_from_datetime_sql, \PDO::PARAM_STR,
            ];
            $params[':offset_from_datetime2'] = [
                $offset_from_datetime_sql, \PDO::PARAM_STR,
            ];
            $params[':offset_from_id'] = [
                $options->fromId, \PDO::PARAM_INT,
            ];
            $ltgt = $order === 'ASC' ? '>' : '<';

            // Because of sqlite lack of tuple comparison support, we use a
            // more complicated condition.
            $where[] = "(items.datetime $ltgt :offset_from_datetime OR
                         (items.datetime = :offset_from_datetime2 AND
                          items.id $ltgt :offset_from_id)
                        )";
        }

        $where_ids = null;
        // extra ids to include in stream
        if (count($options->extraIds) > 0
            // limit the query to a sensible max
            && count($options->extraIds) <= $this->configuration->itemsPerpage) {
            $where_ids = static::$stmt::intRowMatches('items.id', $options->extraIds);
        }

        // finalize items filter
        $where_sql = implode(' AND ', $where);

        // set limit
        $pageSize = $options->pageSize === null ? $this->configuration->itemsPerpage : min($options->pageSize, max(200, $this->configuration->itemsPerpage));

        // first check whether more items are available
        $result = $this->database->exec('SELECT items.id
                   FROM ' . $this->configuration->dbPrefix . 'items AS items, ' . $this->configuration->dbPrefix . 'sources AS sources
                   WHERE items.source=sources.id AND ' . $where_sql . '
                   LIMIT 1 OFFSET ' . ($offset + $pageSize), $params);
        $this->hasMore = count($result) > 0;

        // get items from database
        $select = 'SELECT
            items.id, datetime, items.title AS title, content, unread, starred, source, thumbnail, icon, uid, link, updatetime, author, sources.title as sourcetitle, sources.tags as tags
            FROM ' . $this->configuration->dbPrefix . 'items AS items, ' . $this->configuration->dbPrefix . 'sources AS sources
            WHERE items.source=sources.id AND';
        $order_sql = 'ORDER BY items.datetime ' . $order . ', items.id ' . $order;

        if ($where_ids !== null) {
            // This UNION is required for the extra explicitely requested items
            // to be included whether or not they would have been excluded by
            // seek, filter, offset rules.
            //
            // SQLite note: the 'entries' SELECT is encapsulated into a
            // SELECT * FROM (...) to fool the SQLite engine into not
            // complaining about 'order by clause should come after union not
            // before'.
            $query = "SELECT * FROM (
                        SELECT * FROM ($select $where_sql $order_sql LIMIT " . $pageSize . ' OFFSET ' . $offset . ") AS entries
                      UNION
                        $select $where_ids
                      ) AS items
                      $order_sql";
        } else {
            $query = "$select $where_sql $order_sql LIMIT " . $pageSize . ' OFFSET ' . $offset;
        }

        return static::$stmt::ensureRowTypes($this->database->exec($query, $params), [
            'id' => DatabaseInterface::PARAM_INT,
            'datetime' => DatabaseInterface::PARAM_DATETIME,
            'unread' => DatabaseInterface::PARAM_BOOL,
            'starred' => DatabaseInterface::PARAM_BOOL,
            'source' => DatabaseInterface::PARAM_INT,
            'tags' => DatabaseInterface::PARAM_CSV,
            'updatetime' => DatabaseInterface::PARAM_DATETIME,
        ]);
    }

    /**
     * returns whether more items for last given
     * get call are available
     */
    public function hasMore(): bool {
        return $this->hasMore;
    }

    /**
     * Obtain new or changed items in the database for synchronization with clients.
     *
     * @param int $sinceId id of last seen item
     * @param DateTime $notBefore cut off time stamp
     * @param DateTime $since timestamp of last seen item
     *
     * @return array<array{id: int, datetime: DateTime, title: string, content: string, unread: bool, starred: bool, source: int, thumbnail: string, icon: string, uid: string, link: string, updatetime: DateTime, author: string, sourcetitle: string, tags: string[]}> of items
     */
    public function sync(int $sinceId, DateTime $notBefore, DateTime $since, int $howMany): array {
        $query = 'SELECT
        items.id, datetime, items.title AS title, content, unread, starred, source, thumbnail, icon, uid, link, updatetime, author, sources.title as sourcetitle, sources.tags as tags
        FROM ' . $this->configuration->dbPrefix . 'items AS items, ' . $this->configuration->dbPrefix . 'sources AS sources
        WHERE items.source=sources.id
            AND (' . static::$stmt::isTrue('unread') .
                 ' OR ' . static::$stmt::isTrue('starred') .
                 ' OR datetime >= :notBefore
                )
            AND (items.id > :sinceId OR
                 (datetime < :notBefore AND updatetime > :since))
        ORDER BY items.id LIMIT :howMany';

        $params = [
            'sinceId' => [$sinceId, \PDO::PARAM_INT],
            'howMany' => [$howMany, \PDO::PARAM_INT],
            'notBefore' => [$notBefore->format('Y-m-d H:i:s'), \PDO::PARAM_STR],
            'since' => [$since->format('Y-m-d H:i:s'), \PDO::PARAM_STR],
        ];

        return static::$stmt::ensureRowTypes($this->database->exec($query, $params), [
            'id' => DatabaseInterface::PARAM_INT,
            'datetime' => DatabaseInterface::PARAM_DATETIME,
            'unread' => DatabaseInterface::PARAM_BOOL,
            'starred' => DatabaseInterface::PARAM_BOOL,
            'source' => DatabaseInterface::PARAM_INT,
            'tags' => DatabaseInterface::PARAM_CSV,
            'updatetime' => DatabaseInterface::PARAM_DATETIME,
        ]);
    }

    /**
     * Lowest id of interest
     *
     * @return int lowest id of interest
     */
    public function lowestIdOfInterest(): int {
        $lowest = static::$stmt::ensureRowTypes(
            $this->database->exec(
                'SELECT id FROM ' . $this->configuration->dbPrefix . 'items AS items
                 WHERE ' . static::$stmt::isTrue('unread') .
                    ' OR ' . static::$stmt::isTrue('starred') .
                ' ORDER BY id LIMIT 1'
            ),
            ['id' => DatabaseInterface::PARAM_INT]
        );
        if ($lowest) {
            return $lowest[0]['id'];
        }

        return 0;
    }

    /**
     * Last id in db
     *
     * @return int last id in db
     */
    public function lastId(): int {
        $lastId = static::$stmt::ensureRowTypes(
            $this->database->exec(
                'SELECT id FROM ' . $this->configuration->dbPrefix . 'items AS items
                 ORDER BY id DESC LIMIT 1'
            ),
            ['id' => DatabaseInterface::PARAM_INT]
        );
        if ($lastId) {
            return $lastId[0]['id'];
        }

        return 0;
    }

    /**
     * return all thumbnails
     *
     * @param string $thumbnail name
     *
     * @return bool true if thumbnail is still in use
     */
    public function hasThumbnail(string $thumbnail): bool {
        $res = $this->database->exec(
            'SELECT count(*) AS amount
                   FROM ' . $this->configuration->dbPrefix . 'items
                   WHERE thumbnail=:thumbnail',
            [':thumbnail' => $thumbnail]
        );
        $amount = $res[0]['amount'];
        if ($amount == 0) {
            $this->logger->debug('thumbnail not found: ' . $thumbnail);
        }

        return $amount > 0;
    }

    /**
     * return all icons
     *
     * @param string $icon file
     *
     * @return bool true if icon is still in use
     */
    public function hasIcon(string $icon): bool {
        $res = $this->database->exec(
            'SELECT count(*) AS amount
                   FROM ' . $this->configuration->dbPrefix . 'items
                   WHERE icon=:icon',
            [':icon' => $icon]
        );

        return $res[0]['amount'] > 0;
    }

    /**
     * returns the amount of entries in database which are unread
     *
     * @return int amount of entries in database which are unread
     */
    public function numberOfUnread(): int {
        $res = $this->database->exec('SELECT count(*) AS amount
                   FROM ' . $this->configuration->dbPrefix . 'items
                   WHERE ' . static::$stmt::isTrue('unread'));

        return $res[0]['amount'];
    }

    /**
     * returns the amount of total, unread, starred entries in database
     *
     * @return array{total: int, unread: int, starred: int} mount of total, unread, starred entries in database
     */
    public function stats(): array {
        $res = $this->database->exec('SELECT
            COUNT(*) AS total,
            ' . static::$stmt::sumBool('unread') . ' AS unread,
            ' . static::$stmt::sumBool('starred') . ' AS starred
            FROM ' . $this->configuration->dbPrefix . 'items;');
        $res = static::$stmt::ensureRowTypes($res, [
            'total' => DatabaseInterface::PARAM_INT,
            'unread' => DatabaseInterface::PARAM_INT,
            'starred' => DatabaseInterface::PARAM_INT,
        ]);

        return $res[0];
    }

    /**
     * returns the datetime of the last item update or user action in db
     */
    public function lastUpdate(): ?DateTimeImmutable {
        $res = $this->database->exec('SELECT
            MAX(updatetime) AS last_update_time
            FROM ' . $this->configuration->dbPrefix . 'items;');
        $lastUpdate = $res[0]['last_update_time'];

        return $lastUpdate !== null ? new DateTimeImmutable($lastUpdate) : null;
    }

    /**
     * returns the statuses of items last update
     *
     * @param DateTime $since minimal date of returned items
     *
     * @return array<array{id: int, unread: bool, starred: bool}> of unread, starred, etc. status of specified items
     */
    public function statuses(DateTime $since): array {
        $res = $this->database->exec(
            'SELECT id, unread, starred
            FROM ' . $this->configuration->dbPrefix . 'items
            WHERE ' . $this->configuration->dbPrefix . 'items.updatetime > :since;',
            [':since' => [$since->format('Y-m-d H:i:s'), \PDO::PARAM_STR]]
        );
        $res = static::$stmt::ensureRowTypes($res, [
            'id' => DatabaseInterface::PARAM_INT,
            'unread' => DatabaseInterface::PARAM_BOOL,
            'starred' => DatabaseInterface::PARAM_BOOL,
        ]);

        return $res;
    }

    /**
     * bulk update of item status
     *
     * @param array<array{id: int, unread?: mixed, starred?: mixed, datetime?: string}> $statuses array of statuses updates
     */
    public function bulkStatusUpdate(array $statuses): void {
        $sql = [];
        foreach ($statuses as $status) {
            if (array_key_exists('id', $status)) {
                $id = (int) $status['id'];
                if ($id > 0) {
                    $statusUpdate = null;

                    // sanitize statuses
                    foreach (['unread', 'starred'] as $sk) {
                        if (array_key_exists($sk, $status)) {
                            if ($status[$sk] == 'true') {
                                $statusUpdate = [
                                    'sk' => $sk,
                                    'sql' => static::$stmt::isTrue($sk),
                                ];
                            } elseif ($status[$sk] == 'false') {
                                $statusUpdate = [
                                    'sk' => $sk,
                                    'sql' => static::$stmt::isFalse($sk),
                                ];
                            }
                        }
                    }

                    // sanitize update time
                    if (array_key_exists('datetime', $status)) {
                        $updateDate = new DateTime($status['datetime']);
                    } else {
                        $updateDate = null;
                    }

                    if ($statusUpdate !== null && $updateDate !== null) {
                        $sk = $statusUpdate['sk'];
                        if (array_key_exists($id, $sql)) {
                            // merge status updates for the same entry and
                            // ensure all saved status updates have been made
                            // after the last server update for this entry.
                            if (!array_key_exists($sk, $sql[$id]['updates'])
                                || $updateDate > $sql[$id]['datetime']) {
                                $sql[$id]['updates'][$sk] = $statusUpdate['sql'];
                            }
                            if ($updateDate < $sql[$id]['datetime']) {
                                $sql[$id]['datetime'] = $updateDate;
                            }
                        } else {
                            // create new status update
                            $sql[$id] = [
                                'updates' => [$sk => $statusUpdate['sql']],
                                'datetime' => $updateDate->format('Y-m-d H:i:s'),
                            ];
                        }
                    }
                }
            }
        }

        if ($sql) {
            $this->database->beginTransaction();
            foreach ($sql as $id => $q) {
                $params = [
                    ':id' => [$id, \PDO::PARAM_INT],
                    ':statusUpdate' => [$q['datetime'], \PDO::PARAM_STR],
                ];
                $updated = $this->database->execute(
                    'UPDATE ' . $this->configuration->dbPrefix . 'items
                    SET ' . implode(', ', array_values($q['updates'])) . '
                    WHERE id = :id AND updatetime < :statusUpdate',
                    $params
                );
                if ($updated->rowCount() === 0) {
                    // entry status was updated in between so updatetime must
                    // be updated to ensure client side consistency of
                    // statuses.
                    $this->database->exec(
                        'UPDATE ' . $this->configuration->dbPrefix . 'items
                         SET ' . static::$stmt::rowTouch('updatetime') . '
                         WHERE id = :id',
                        [':id' => [$id, \PDO::PARAM_INT]]
                    );
                }
            }
            $this->database->commit();
        }
    }
}
