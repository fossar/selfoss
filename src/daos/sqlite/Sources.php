<?php

declare(strict_types=1);

namespace Selfoss\daos\sqlite;

use Override;

/**
 * Class for accessing persistent saved sources -- mysql
 *
 * @copyright  Copyright (c) Harald Lapp <harald.lapp@gmail.com>
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Harald Lapp <harald.lapp@gmail.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
final class Sources extends \Selfoss\daos\mysql\Sources {
    /** @var class-string SQL helper */
    protected static string $stmt = Statements::class;

    /**
     * wrap insert statement to return id
     *
     * @param string $query sql statement
     * @param array<string, mixed> $params sql params
     *
     * @return int id after insert
     */
    #[Override]
    protected function insert(string $query, array $params): int {
        $this->database->exec($query, $params);
        $res = $this->database->exec('SELECT last_insert_rowid() as lastid');

        return (int) $res[0]['lastid'];
    }
}
