<?php

declare(strict_types=1);

namespace Selfoss\helpers\Database;

use PDO;

interface PdoFactory {
    public function create(): PDO;
}
