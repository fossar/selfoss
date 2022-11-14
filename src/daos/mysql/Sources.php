<?php

declare(strict_types=1);

namespace daos\mysql;

use daos\DatabaseInterface;
use helpers\Configuration;

/**
 * Class for accessing persistent saved sources -- mysql
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Sources implements \daos\SourcesInterface {
    /** @var class-string SQL helper */
    protected static $stmt = Statements::class;

    /** @var Configuration configuration */
    private $configuration;

    /** @var DatabaseInterface database connection */
    protected $database;

    public function __construct(Configuration $configuration, DatabaseInterface $database) {
        $this->configuration = $configuration;
        $this->database = $database;
    }

    /**
     * add new source
     *
     * @param string[] $tags
     * @param string $spout the source type
     * @param array $params depends from spout
     *
     * @return int new id
     */
    public function add(string $title, array $tags, string $filter, string $spout, array $params): int {
        return $this->database->insert('INSERT INTO ' . $this->configuration->dbPrefix . 'sources (title, tags, filter, spout, params) VALUES (:title, :tags, :filter, :spout, :params)', [
            ':title' => trim($title),
            ':tags' => static::$stmt::csvRow($tags),
            ':filter' => $filter,
            ':spout' => $spout,
            ':params' => htmlentities(json_encode($params)),
        ]);
    }

    /**
     * edit source
     *
     * @param int $id the source id
     * @param string $title new title
     * @param string[] $tags new tags
     * @param string $spout new spout
     * @param array $params the new params
     */
    public function edit(int $id, string $title, array $tags, string $filter, string $spout, array $params): void {
        $this->database->exec('UPDATE ' . $this->configuration->dbPrefix . 'sources SET title=:title, tags=:tags, filter=:filter, spout=:spout, params=:params WHERE id=:id', [
            ':title' => trim($title),
            ':tags' => static::$stmt::csvRow($tags),
            ':filter' => $filter,
            ':spout' => $spout,
            ':params' => htmlentities(json_encode($params)),
            ':id' => $id,
        ]);
    }

    /**
     * delete source
     */
    public function delete(int $id): void {
        $this->database->exec('DELETE FROM ' . $this->configuration->dbPrefix . 'sources WHERE id=:id', [':id' => $id]);

        // delete items of this source
        $this->database->exec('DELETE FROM ' . $this->configuration->dbPrefix . 'items WHERE source=:id', [':id' => $id]);
    }

    /**
     * save error message
     *
     * @param int $id the source id
     * @param string $error error message
     */
    public function error(int $id, string $error): void {
        if (strlen($error) === 0) {
            $arr = [
                ':id' => $id,
                ];
            $setarg = 'NULL';
        } else {
            $arr = [
                ':id' => $id,
                ':error' => $error,
            ];
            $setarg = ':error';
        }

        $this->database->exec('UPDATE ' . $this->configuration->dbPrefix . 'sources SET error=' . $setarg . ' WHERE id=:id', $arr);
    }

    /**
     * sets the last updated timestamp
     *
     * @param int $id the source id
     * @param ?int $lastEntry timestamp of the newest item or NULL when no items were added
     */
    public function saveLastUpdate(int $id, ?int $lastEntry): void {
        $this->database->exec('UPDATE ' . $this->configuration->dbPrefix . 'sources SET lastupdate=:lastupdate WHERE id=:id',
            [
                ':id' => $id,
                ':lastupdate' => time(),
            ]);

        if ($lastEntry !== null) {
            $this->database->exec('UPDATE ' . $this->configuration->dbPrefix . 'sources SET lastentry=:lastentry WHERE id=:id',
                [
                    ':id' => $id,
                    ':lastentry' => $lastEntry,
                ]);
        }
    }

    /**
     * returns all sources
     *
     * @return mixed all sources
     */
    public function getByLastUpdate() {
        $ret = $this->database->exec('SELECT id, title, tags, spout, params, filter, error, lastupdate, lastentry FROM ' . $this->configuration->dbPrefix . 'sources ORDER BY lastupdate ASC');
        $ret = static::$stmt::ensureRowTypes($ret, [
            'id' => DatabaseInterface::PARAM_INT,
            'lastupdate' => DatabaseInterface::PARAM_INT | DatabaseInterface::PARAM_NULL,
            'lastentry' => DatabaseInterface::PARAM_INT | DatabaseInterface::PARAM_NULL,
        ]);

        return $ret;
    }

    /**
     * returns specified source (null if it doesnt exist)
     * or all sources if no id specified
     *
     * @param ?int $id specification of source id
     *
     * @return ?mixed specified source or all sources
     */
    public function get(?int $id = null) {
        // select source by id if specified or return all sources
        if (isset($id)) {
            $ret = $this->database->exec('SELECT id, title, tags, spout, params, filter, error, lastupdate, lastentry FROM ' . $this->configuration->dbPrefix . 'sources WHERE id=:id', [':id' => $id]);
            $ret = static::$stmt::ensureRowTypes($ret, [
                'id' => DatabaseInterface::PARAM_INT,
                'lastupdate' => DatabaseInterface::PARAM_INT | DatabaseInterface::PARAM_NULL,
                'lastentry' => DatabaseInterface::PARAM_INT | DatabaseInterface::PARAM_NULL,
            ]);
            if (isset($ret[0])) {
                $ret = $ret[0];
            } else {
                $ret = null;
            }
        } else {
            $ret = $this->database->exec('SELECT id, title, tags, spout, params, filter, error, lastupdate, lastentry FROM ' . $this->configuration->dbPrefix . 'sources ORDER BY error DESC, lower(title) ASC');
            $ret = static::$stmt::ensureRowTypes($ret, [
                'id' => DatabaseInterface::PARAM_INT,
                'tags' => DatabaseInterface::PARAM_CSV,
                'lastupdate' => DatabaseInterface::PARAM_INT | DatabaseInterface::PARAM_NULL,
                'lastentry' => DatabaseInterface::PARAM_INT | DatabaseInterface::PARAM_NULL,
            ]);
        }

        return $ret;
    }

    /**
     * returns all sources including unread count
     *
     * @return mixed all sources
     */
    public function getWithUnread() {
        $ret = $this->database->exec('SELECT
            sources.id, sources.title, COUNT(items.id) AS unread
            FROM ' . $this->configuration->dbPrefix . 'sources AS sources
            LEFT OUTER JOIN ' . $this->configuration->dbPrefix . 'items AS items
                 ON (items.source=sources.id AND ' . static::$stmt::isTrue('items.unread') . ')
            GROUP BY sources.id, sources.title
            ORDER BY lower(sources.title) ASC');

        return static::$stmt::ensureRowTypes($ret, [
            'id' => DatabaseInterface::PARAM_INT,
            'unread' => DatabaseInterface::PARAM_INT,
        ]);
    }

    /**
     * returns all sources including last icon
     *
     * @return mixed all sources
     */
    public function getWithIcon() {
        $ret = $this->database->exec('SELECT
                sources.id, sources.title, sources.tags, sources.spout,
                sources.params, sources.filter, sources.error, sources.lastentry,
                sourceicons.icon AS icon
            FROM ' . $this->configuration->dbPrefix . 'sources AS sources
            LEFT OUTER JOIN
                (SELECT items.source, icon
                 FROM ' . $this->configuration->dbPrefix . 'items AS items,
                      (SELECT source, MAX(id) as maxid
                       FROM ' . $this->configuration->dbPrefix . 'items AS items
                       WHERE icon IS NOT NULL AND icon != \'\'
                       GROUP BY items.source) AS icons
                 WHERE items.id=icons.maxid AND items.source=icons.source
                 ) AS sourceicons
                ON sources.id=sourceicons.source
            ORDER BY ' . static::$stmt::nullFirst('sources.error', 'DESC') . ', lower(sources.title)');

        return static::$stmt::ensureRowTypes($ret, [
            'id' => DatabaseInterface::PARAM_INT,
            'tags' => DatabaseInterface::PARAM_CSV,
            'lastentry' => DatabaseInterface::PARAM_INT | DatabaseInterface::PARAM_NULL,
        ]);
    }

    /**
     * returns all tags
     *
     * @return mixed all sources
     */
    public function getAllTags() {
        $result = $this->database->exec('SELECT tags FROM ' . $this->configuration->dbPrefix . 'sources');
        $tags = [];
        foreach ($result as $res) {
            $tags = array_merge($tags, explode(',', $res['tags']));
        }
        $tags = array_unique($tags);

        return $tags;
    }

    /**
     * returns tags of a source
     *
     * @return mixed tags of a source
     */
    public function getTags(int $id) {
        $result = $this->database->exec('SELECT tags FROM ' . $this->configuration->dbPrefix . 'sources WHERE id=:id', [':id' => $id]);
        $tags = [];
        $tags = array_merge($tags, explode(',', $result[0]['tags']));
        $tags = array_unique($tags);

        return $tags;
    }

    /**
     * test if a source is already present using title, spout and params.
     * if present returns the id, else returns 0
     *
     * @param  string  $spout the source type
     * @param  array   $params depends from spout
     *
     * @return int id if any record is found
     */
    public function checkIfExists(string $title, string $spout, array $params): int {
        // Check if a entry exists with same title, spout and params
        $result = $this->database->exec('SELECT id FROM ' . $this->configuration->dbPrefix . 'sources WHERE title=:title AND spout=:spout AND params=:params', [
            ':title' => trim($title),
            ':spout' => $spout,
            ':params' => htmlentities(json_encode($params)),
        ]);
        if ($result) {
            return $result[0]['id'];
        }

        return 0;
    }
}
