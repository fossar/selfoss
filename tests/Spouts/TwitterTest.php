<?php

declare(strict_types=1);

namespace Tests\Spouts;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use helpers\HtmlString;
use helpers\WebClient;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slince\Di\Container;
use spouts\spout;
use spouts\twitter;

final class TwitterTest extends TestCase {
    private function getResourceData(string $fileName): string {
        return @file_get_contents(__DIR__ . '/resources/Twitter/' . $fileName) ?: "Unable to load test resource {$fileName}.";
    }

    /**
     * @template T
     *
     * @param class-string<spout<T>> $spout
     * @param Response[] $responses
     *
     * @return spout<T>
     */
    private function makeSpout($spout, array $responses): spout {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $httpClientConfig = [
            'handler' => $stack,
        ];

        $container = new Container();
        $container->setDefaults(['shared' => false]);

        $container
            ->register(Logger::class)
            ->setArgument('name', 'selfoss')
            ->setShared(true)
        ;
        $container
            ->register(WebClient::class, function() use ($httpClientConfig) {
                $stub = $this->createMock(WebClient::class);
                $stub->method('createHttpClientConfig')->willReturn($httpClientConfig);

                return $stub;
            })
        ;

        return $container->get($spout);
    }

    public function testListTimeline(): void {
        $list = $this->makeSpout(
            twitter\listtimeline::class,
            [
                new Response(200, ['Content-Type' => 'application/json;charset=utf-8'], $this->getResourceData('list-FilipHorky-1463400275650174980.json')),
            ]
        );

        $params = [
            'consumer_key' => 'dummy',
            'consumer_secret' => 'dummy',
            'slug' => '1463400275650174980',
            'owner_screen_name' => 'FilipHorky',
        ];

        $list->load($params);
        $items = is_array($list->getItems()) ? $list->getItems() : iterator_to_array($list->getItems());

        // Tests Unicode characters.
        $this->assertEquals(HtmlString::fromRaw('několik stolů, kterým se říká oltáře, je tady výtah, několik stolů a kožené sedačky. Vstoupit sem můžete jen s povolením a to se týká jak příbuzných tak i knězů. <a href="https://twitter.com/Fbeyeee/status/1627389095453376512/photo/1" target="_blank" rel="noreferrer">pic.twitter.com/kTezfksbBT</a>'), $items[0]->getTitle());
        $this->assertEquals(HtmlString::fromRaw("<p><a href=\"https://pbs.twimg.com/media/FpWjj96XgAIi6cN.jpg:large\"><img src=\"https://pbs.twimg.com/media/FpWjj96XgAIi6cN.jpg:small\" alt=\"\"></a></p>\n"), $items[0]->getContent());
        // Tests Unicode characters that span multiple code points.
        $this->assertEquals(HtmlString::fromRaw('Jestli Kyjev za tři dny, tak "Polsko srazí na kolena"...za jednu hodinu, a bez jediného mrtvého vojáka. A to prosím konvenčními zbraněmi. A tím vrátí🇵🇱do doby kamenné. 50-60 strategických objektů zničí za 20 minut :)) Tak proč tam posílat armádu? Na co posílat tanky na Varšavu? <a href="https://twitter.com/Fbeyeee/status/1627328192787742721/video/1" target="_blank" rel="noreferrer">pic.twitter.com/nVfXaGyQ9h</a>'), $items[49]->getTitle());
        $this->assertEquals(HtmlString::fromRaw(''), $items[49]->getContent());
    }

    public function testSearch(): void {
        $list = $this->makeSpout(
            twitter\Search::class,
            [
                new Response(200, ['Content-Type' => 'application/json;charset=utf-8'], $this->getResourceData('search-heart.json')),
            ]
        );

        $params = [
            'consumer_key' => 'dummy',
            'consumer_secret' => 'dummy',
            'query' => '<3',
        ];

        $list->load($params);
        $items = is_array($list->getItems()) ? $list->getItems() : iterator_to_array($list->getItems());

        // Tests HTML-special characters (<).
        $this->assertEquals(HtmlString::fromRaw('tag someone you would love to spend time with &lt;3 <a href="https://twitter.com/stinkykatie/status/1627392132796280832/photo/1" target="_blank" rel="noreferrer">pic.twitter.com/yuZswYlY5g</a>'), $items[1]->getTitle());
    }

    public function testUserTimeline(): void {
        $list = $this->makeSpout(
            twitter\usertimeline::class,
            [
                new Response(200, ['Content-Type' => 'application/json;charset=utf-8'], $this->getResourceData('user-JoeTegtmeyer.json')),
            ]
        );

        $params = [
            'consumer_key' => 'dummy',
            'consumer_secret' => 'dummy',
            'username' => 'JoeTegtmeyer',
        ];

        $list->load($params);
        $items = is_array($list->getItems()) ? $list->getItems() : iterator_to_array($list->getItems());

        // Tests HTML-special characters (ampersand).
        $this->assertEquals(HtmlString::fromRaw("17 February 2023 Giga Texas VIDEO … Cold &amp; windy! Paint on S end, W entrance concrete, trees &amp; trenching. Roof solar light install. E traffic circle progress &amp; supercharger canopy. Switchyard footings for “A-frame” &amp; C/Bs arrived. Die shop progress too!\n\n<a href=\"https://youtu.be/riN5nHoGp30\" target=\"_blank\" rel=\"noreferrer\">youtu.be/riN5nHoGp30</a>"), $items[4]->getTitle());
    }
}
