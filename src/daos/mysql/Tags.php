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
class Tags implements \daos\TagsInterface {
    /** @var class-string SQL helper */
    protected static string $stmt = Statements::class;

    public function __construct(
        private readonly Configuration $configuration,
        protected DatabaseInterface $database
    ) {
    }

    /**
     * save given tag color
     */
    public function saveTagColor(string $tag, string $color): void {
        if ($this->hasTag($tag) === true) {
            $this->database->exec('UPDATE ' . $this->configuration->dbPrefix . 'tags SET color=:color WHERE tag=:tag', [
                ':tag' => $tag,
                ':color' => $color,
            ]);
        } else {
            $this->database->exec('INSERT INTO ' . $this->configuration->dbPrefix . 'tags (tag, color) VALUES (:tag, :color)', [
                ':tag' => $tag,
                ':color' => $color,
            ]);
        }
    }

    /**
     * save given tag with random color
     */
    public function autocolorTag(string $tag): void {
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
     * @return array<array{tag: string, color: string}>
     */
    public function get(): array {
        /** @var array<array{tag: string, color: string}> */
        $result = $this->database->exec('SELECT tag, color FROM ' . $this->configuration->dbPrefix . 'tags ORDER BY LOWER(tag);');

        return $result;
    }

    /**
     * returns all tags with color and unread count
     *
     * @return array<array{tag: string, color: string, unread: int}>
     */
    public function getWithUnread(): array {
        $select = 'SELECT tag, color, COUNT(items.id) AS unread
                   FROM ' . $this->configuration->dbPrefix . 'tags AS tags,
                        ' . $this->configuration->dbPrefix . 'sources AS sources
                   LEFT OUTER JOIN ' . $this->configuration->dbPrefix . 'items AS items
                       ON (items.source=sources.id AND ' . static::$stmt::isTrue('items.unread') . ')
                   WHERE ' . static::$stmt::csvRowMatches('sources.tags', 'tags.tag') . '
                   GROUP BY tags.tag, tags.color
                   ORDER BY LOWER(tags.tag);';

        return static::$stmt::ensureRowTypes($this->database->exec($select), ['unread' => DatabaseInterface::PARAM_INT]);
    }

    /**
     * remove all unused tag color definitions
     *
     * @param string[] $tags available tags
     */
    public function cleanup(array $tags): void {
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
     * @return bool true if color is used by an tag
     */
    private function isColorUsed(string $color): bool {
        $res = $this->database->exec('SELECT COUNT(*) AS amount FROM ' . $this->configuration->dbPrefix . 'tags WHERE color=:color', [':color' => $color]);

        return $res[0]['amount'] > 0;
    }

    /**
     * check whether tag color is defined.
     *
     * @return bool true if color is used by an tag
     */
    public function hasTag(string $tag): bool {
        if ($this->configuration->dbType === 'mysql') {
            $where = 'WHERE tag = _utf8mb4 :tag COLLATE utf8mb4_bin';
        } else {
            $where = 'WHERE tag=:tag';
        }
        $res = $this->database->exec('SELECT COUNT(*) AS amount FROM ' . $this->configuration->dbPrefix . 'tags ' . $where, [':tag' => $tag]);

        return $res[0]['amount'] > 0;
    }

    /**
     * delete tag
     */
    public function delete(string $tag): void {
        $this->database->exec('DELETE FROM ' . $this->configuration->dbPrefix . 'tags WHERE tag=:tag', [':tag' => $tag]);
    }
}
