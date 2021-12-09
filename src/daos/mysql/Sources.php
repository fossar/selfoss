<?php

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
     * @param string $title
     * @param string[] $tags
     * @param string $filter
     * @param string $spout the source type
     * @param array $params depends from spout
     *
     * @return int new id
     */
    public function add($title, array $tags, $filter, $spout, array $params) {
        $stmt = static::$stmt;

        return $this->database->insert('INSERT INTO ' . $this->configuration->dbPrefix . 'sources', [
            'title' => trim($title),
            'tags' => $stmt::csvRow($tags),
            'filter' => $filter,
            'spout' => $spout,
            'params' => htmlentities(json_encode($params)),
        ]);
    }

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
    public function edit($id, $title, array $tags, $filter, $spout, array $params) {
        $stmt = static::$stmt;
        $this->database->query(
            'UPDATE ' . $this->configuration->dbPrefix . 'sources SET',
            [
                'title' => trim($title),
                'tags' => $stmt::csvRow($tags),
                'filter' => $filter,
                'spout' => $spout,
                'params' => htmlentities(json_encode($params)),
            ],
            'WHERE',
            [
                'id' => $id,
            ]
        );
    }

    /**
     * delete source
     *
     * @param int $id
     *
     * @return void
     */
    public function delete($id) {
        $this->database->query('DELETE FROM ' . $this->configuration->dbPrefix . 'sources WHERE', ['id' => $id]);

        // delete items of this source
        $this->database->query('DELETE FROM ' . $this->configuration->dbPrefix . 'items WHERE', ['source' => $id]);
    }

    /**
     * save error message
     *
     * @param int $id the source id
     * @param string $error error message
     *
     * @return void
     */
    public function error($id, $error) {
        $this->database->query(
            'UPDATE ' . $this->configuration->dbPrefix . 'sources SET',
            [
                'error' => strlen($error) === 0 ? null : $error,
            ],
            'WHERE',
            [
                'id' => $id,
            ]
        );
    }

    /**
     * sets the last updated timestamp
     *
     * @param int $id the source id
     * @param ?int $lastEntry timestamp of the newest item or NULL when no items were added
     *
     * @return void
     */
    public function saveLastUpdate($id, $lastEntry) {
        $this->database->query(
            'UPDATE ' . $this->configuration->dbPrefix . 'sources SET',
            [
                'lastupdate' => time(),
            ],
            'WHERE',
            [
                'id' => $id,
            ]
        );

        if ($lastEntry !== null) {
            $this->database->query(
                'UPDATE ' . $this->configuration->dbPrefix . 'sources SET',
                [
                    'lastentry' => $lastEntry,
                ],
                'WHERE',
                [
                    'id' => $id,
                ]
            );
        }
    }

    /**
     * returns all sources
     *
     * @return mixed all sources
     */
    public function getByLastUpdate() {
        $ret = $this->database->query('SELECT id, title, tags, spout, params, filter, error, lastupdate, lastentry FROM ' . $this->configuration->dbPrefix . 'sources ORDER BY lastupdate ASC');

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
    public function get($id = null) {
        $stmt = static::$stmt;
        // select source by id if specified or return all sources
        if (isset($id)) {
            $ret = $this->database->query('SELECT id, title, tags, spout, params, filter, error, lastupdate, lastentry FROM ' . $this->configuration->dbPrefix . 'sources WHERE', ['id' => $id]);
            if ($ret->getRowCount() > 0) {
                $ret = $ret->fetch();
            } else {
                $ret = null;
            }
        } else {
            $ret = $this->database->query('SELECT id, title, tags, spout, params, filter, error, lastupdate, lastentry FROM ' . $this->configuration->dbPrefix . 'sources ORDER BY error DESC, lower(title) ASC');
            $ret = $stmt::ensureRowTypes($ret, [
                'tags' => DatabaseInterface::PARAM_CSV,
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
        $stmt = static::$stmt;
        $ret = $this->database->query('SELECT
            sources.id, sources.title, COUNT(items.id) AS unread
            FROM ' . $this->configuration->dbPrefix . 'sources AS sources
            LEFT OUTER JOIN ' . $this->configuration->dbPrefix . 'items AS items
                 ON (items.source=sources.id AND ' . $stmt::isTrue('items.unread') . ')
            GROUP BY sources.id, sources.title
            ORDER BY lower(sources.title) ASC');

        return $stmt::ensureRowTypes($ret, [
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
        $stmt = static::$stmt;
        $ret = $this->database->query('SELECT
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
            ORDER BY ' . $stmt::nullFirst('sources.error', 'DESC') . ', lower(sources.title)');

        return $stmt::ensureRowTypes($ret, [
            'id' => DatabaseInterface::PARAM_INT,
            'tags' => DatabaseInterface::PARAM_CSV,
        ]);
    }

    /**
     * test if the value of a specified field is valid
     *
     * @param   string      $name
     * @param   mixed       $value
     *
     * @return  bool
     */
    public function isValid($name, $value) {
        $return = false;

        switch ($name) {
        case 'id':
            $return = is_numeric($value);
            break;
        }

        return $return;
    }

    /**
     * returns all tags
     *
     * @return mixed all sources
     */
    public function getAllTags() {
        $result = $this->database->query('SELECT tags FROM ' . $this->configuration->dbPrefix . 'sources');
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
     * @param int $id
     *
     * @return mixed tags of a source
     */
    public function getTags($id) {
        $result = $this->database->query('SELECT tags FROM ' . $this->configuration->dbPrefix . 'sources WHERE', ['id' => $id]);
        $tags = [];
        $tags = array_merge($tags, explode(',', $result->fetch()['tags']));
        $tags = array_unique($tags);

        return $tags;
    }

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
    public function checkIfExists($title, $spout, array $params) {
        // Check if a entry exists with same title, spout and params
        $result = $this->database->query('SELECT id FROM ' . $this->configuration->dbPrefix . 'sources WHERE', [
            'title' => trim($title),
            'spout' => $spout,
            'params' => htmlentities(json_encode($params)),
        ]);
        if ($result) {
            return $result->fetch()['id'];
        }

        return 0;
    }
}
