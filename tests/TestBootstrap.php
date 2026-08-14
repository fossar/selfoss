<?php

declare(strict_types=1);

namespace Tests;

use Kama\LiteWireDI\Container;
use Monolog\Logger;
use Selfoss\helpers\FeedReader;
use VStelmakh\UrlHighlight\UrlHighlight;

final class TestBootstrap {
    public static function container(): Container {
        $container = new Container();

        $container->set(Logger::class, [
            'name' => 'selfoss',
            'timezone' => null,
        ]);

        $container->set(FeedReader::class, ['cache' => null]);

        $container->set(UrlHighlight::class, static fn(Container $container) => new UrlHighlight());

        return $container;
    }
}
