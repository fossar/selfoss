<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$bootstrap = new Selfoss\Bootstrap();
$container = $bootstrap->bootWebApplication();
$routes = $container->getByType(Selfoss\Routes::class);
$routes->run();
