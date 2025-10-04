<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Selfoss\helpers\UpdateVisitor;

chdir(__DIR__);
require __DIR__ . '/src/common.php';

/** @var ContainerInterface $container */
$loader = $container->get(Selfoss\helpers\ContentLoader::class);
$updateVisitor = new class implements UpdateVisitor {
    public function started(int $count): void {
    }

    public function sourceUpdated(): void {
    }

    public function finished(): void {
    }
};
$loader->update($updateVisitor);
