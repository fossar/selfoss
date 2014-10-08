<?php

chdir(__DIR__);
require(__DIR__.'/common.php');

$f3->set('FTRSS_DATA_DIR', __dir__.'/data/fulltextrss');

$loader = new \helpers\ContentLoader();
$loader->update();

?>
