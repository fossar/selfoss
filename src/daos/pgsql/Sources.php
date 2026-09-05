<?php

declare(strict_types=1);

namespace Selfoss\daos\pgsql;

use Override;

/**
 * Class for accessing persistant saved sources -- postgresql
 *
 * @copyright   Copyright (c) Michael Jackson <michael.o.jackson@gmail.com>
 * @license     GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author      Michael Jackson <michael.o.jackson@gmail.com>
 * @author      Tobias Zeising <tobias.zeising@aditu.de>
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
        $res = $this->database->exec("$query RETURNING id", $params);

        return $res[0]['id'];
    }
}
