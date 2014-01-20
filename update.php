<?php

chdir(__DIR__);
require(__DIR__.'/common.php');

$loader = new \helpers\ContentLoader();
$loader->update();

?>
