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
class Tags implements \daos\TagsInterface {
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
     * save given tag color
     *
     * @param string $tag
     * @param string $color
     *
     * @return void
     */
    public function saveTagColor($tag, $color) {
        if ($this->hasTag($tag) === true) {
            $this->database->query(
                'UPDATE ' . $this->configuration->dbPrefix . 'tags SET', [
                    'color' => $color,
                ],
                'WHERE'
                [
                    'tag' => $tag,
                ]
            );
        } else {
            $this->database->query('INSERT INTO ' . $this->configuration->dbPrefix . 'tags', [
                'tag' => $tag,
                'color' => $color,
            ]);
        }
    }

    /**
     * save given tag with random color
     *
     * @param string $tag
     *
     * @return void
     */
    public function autocolorTag($tag) {
        if (strlen(trim($tag)) === 0) {
            return;
        }

        // tag color allready defined
        if ($this->hasTag($tag)) {
            return;
        }

        // get unused random color
        while (true) {
            $color = \helpers\Color::randomColor();
            if ($this->isColorUsed($color) === false) {
                break;
            }
        }

        $this->saveTagColor($tag, $color);
    }

    /**
     * returns all tags with color
     *
     * @return array of all tags
     */
    public function get() {
        return $this->database->query('
            SELECT tag, color FROM ' . $this->configuration->dbPrefix . 'tags ORDER BY LOWER(tag);
        ');
    }

    /**
     * returns all tags with color and unread count
     *
     * @return array of all tags
     */
    public function getWithUnread() {
        $stmt = static::$stmt;
        return $this->database->query('
            SELECT tag, color, COUNT(items.id) AS unread
                   FROM ' . $this->configuration->dbPrefix . 'tags AS tags,
                        ' . $this->configuration->dbPrefix . 'sources AS sources
                   LEFT OUTER JOIN ' . $this->configuration->dbPrefix . 'items AS items
                       ON (items.source=sources.id AND ' . $stmt::isTrue('items.unread') . ')
                   WHERE ' . $stmt::csvRowMatches('sources.tags', 'tags.tag') . '
                   GROUP BY tags.tag, tags.color
                   ORDER BY LOWER(tags.tag);
        ');
    }

    /**
     * remove all unused tag color definitions
     *
     * @param array $tags available tags
     *
     * @return void
     */
    public function cleanup(array $tags) {
        $tagsInDb = $this->get();
        foreach ($tagsInDb as $tag) {
            if (in_array($tag['tag'], $tags, true) === false) {
                $this->delete($tag['tag']);
            }
        }
    }

    /**
     * returns whether a color is used or not
     *
     * @param string $color
     *
     * @return bool true if color is used by an tag
     */
    private function isColorUsed($color) {
        $res = $this->database->query('SELECT COUNT(*) AS amount FROM ' . $this->configuration->dbPrefix . 'tags WHERE', ['color' => $color]);

        return $res->fetch()['amount'] > 0;
    }

    /**
     * check whether tag color is defined.
     *
     * @param string $tag
     *
     * @return bool true if color is used by an tag
     */
    public function hasTag($tag) {
        if ($this->configuration->dbType === 'mysql') {
            $tagCompared = $this->connection::literal('_utf8mb4 ? COLLATE utf8mb4_general_ci', $tag);
        } else {
            $tagCompared = $tag;
        }
        $res = $this->database->query('SELECT COUNT(*) AS amount FROM ' . $this->configuration->dbPrefix . 'tags WHERE', [
            'tag' => $tagCompared
        ]);

        return $res->fetch()['amount'] > 0;
    }

    /**
     * delete tag
     *
     * @param string $tag
     *
     * @return void
     */
    public function delete($tag) {
        $this->database->query('DELETE FROM ' . $this->configuration->dbPrefix . 'tags WHERE', ['tag' => $tag]);
    }
}
