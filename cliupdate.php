<?php

chdir(__DIR__);
require __DIR__ . '/src/common.php';

$loader = new \helpers\ContentLoader();
$loader->update();
