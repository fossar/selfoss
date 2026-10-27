<?php

declare(strict_types=1);

namespace Selfoss\helpers\Database;

use PDO;

final readonly class PdoPgsqlFactory implements PdoFactory {
    public function __construct(
        private string $dsn,
        private string $user,
        private string $password
    ) {
    }

    public function create(): PDO {
        $pdo = new PDO($this->dsn, $this->user, $this->password);

        return $pdo;
    }
}
