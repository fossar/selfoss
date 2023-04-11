<?php

declare(strict_types=1);

chdir(__DIR__);
require __DIR__ . '/src/common.php';

use helpers\UpdateVisitor;

/** @var Dice\Dice $dice */
$loader = $dice->create(helpers\ContentLoader::class);
$updateVisitor = new class() implements UpdateVisitor {
    public function started(int $count): void {
    }

    public function sourceUpdated(): void {
    }

    public function finished(): void {
    }
};
$loader->update($updateVisitor);
