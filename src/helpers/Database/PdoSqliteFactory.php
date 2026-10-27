<?php

declare(strict_types=1);

namespace Selfoss\helpers\Database;

use PDO;

final readonly class PdoSqliteFactory implements PdoFactory {
    public function __construct(
        private string $dsn,
    ) {
    }

    public function create(): PDO {
        $pdo = new PDO($this->dsn);

        // https://www.sqlite.org/lang_expr.html#the_like_glob_regexp_match_and_extract_operators
        $pdo->sqliteCreateFunction(
            'regexp',
            [self::class, 'regexp'],
            2,
        );

        return $pdo;
    }

    private static function regexp(string $pattern, string $text): bool {
        return preg_match('/' . addcslashes($pattern, '/') . '/', $text) === 1;
    }
}
