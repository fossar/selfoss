<?php

chdir(__DIR__);
require __DIR__ . '/src/common.php';

$loader = $dice->create(helpers\ContentLoader::class);
$loader->update();
