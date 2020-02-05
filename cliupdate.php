<?php

chdir(__DIR__);
require __DIR__ . '/src/common.php';

$loader = F3::get('CONTAINER')(\helpers\ContentLoader::class);
$loader->update();
