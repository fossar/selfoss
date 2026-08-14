<?php

declare(strict_types=1);

namespace Tests;

use Monolog\Logger;
use Slince\Di\Container;

final class TestBootstrap {
    public static function container(): Container {
        $container = new Container();

        $container->setDefaults(['shared' => false]);

        $container
            ->register(Logger::class)
            ->setArgument('name', 'selfoss')
            ->setShared(true)
        ;

        return $container;
    }
}
