<?php

declare(strict_types=1);

chdir(__DIR__);
require __DIR__ . '/src/common.php';

$loader = $dice->create(helpers\ContentLoader::class);
$loader->update();
