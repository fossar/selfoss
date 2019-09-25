<?php

namespace daos\mysql;

/**
 * Class for accessing persistent saved sources -- mysql
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Tags extends Database {
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
            \F3::get('db')->exec('UPDATE ' . \F3::get('db_prefix') . 'tags SET color=:color WHERE tag=:tag', [
                ':tag' => $tag,
                ':color' => $color
            ]);
        } else {
            \F3::get('db')->exec('INSERT INTO ' . \F3::get('db_prefix') . 'tags (tag, color) VALUES (:tag, :color)', [
                ':tag' => $tag,
                ':color' => $color,
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
        return \F3::get('db')->exec('SELECT
                    tag, color
                   FROM ' . \F3::get('db_prefix') . 'tags
                   ORDER BY LOWER(tag);');
    }

    /**
     * returns all tags with color and unread count
     *
     * @return array of all tags
     */
    public function getWithUnread() {
        $select = 'SELECT tag, color, COUNT(items.id) AS unread
                   FROM ' . \F3::get('db_prefix') . 'tags AS tags,
                        ' . \F3::get('db_prefix') . 'sources AS sources
                   LEFT OUTER JOIN ' . \F3::get('db_prefix') . 'items AS items
                       ON (items.source=sources.id AND ' . $this->stmt->isTrue('items.unread') . ')
                   WHERE ' . $this->stmt->csvRowMatches('sources.tags', 'tags.tag') . '
                   GROUP BY tags.tag, tags.color
                   ORDER BY LOWER(tags.tag);';

        return $this->stmt->ensureRowTypes(\F3::get('db')->exec($select), ['unread' => \daos\PARAM_INT]);
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
        $res = \F3::get('db')->exec('SELECT COUNT(*) AS amount FROM ' . \F3::get('db_prefix') . 'tags WHERE color=:color', [':color' => $color]);

        return $res[0]['amount'] > 0;
    }

    /**
     * check whether tag color is defined.
     *
     * @param string $tag
     *
     * @return bool true if color is used by an tag
     */
    public function hasTag($tag) {
        if (\F3::get('db_type') === 'mysql') {
            $where = 'WHERE tag = _utf8mb4 :tag COLLATE utf8mb4_general_ci';
        } else {
            $where = 'WHERE tag=:tag';
        }
        $res = \F3::get('db')->exec('SELECT COUNT(*) AS amount FROM ' . \F3::get('db_prefix') . 'tags ' . $where, [':tag' => $tag]);

        return $res[0]['amount'] > 0;
    }

    /**
     * delete tag
     *
     * @param string $tag
     *
     * @return void
     */
    public function delete($tag) {
        \F3::get('db')->exec('DELETE FROM ' . \F3::get('db_prefix') . 'tags WHERE tag=:tag', [':tag' => $tag]);
    }
}
