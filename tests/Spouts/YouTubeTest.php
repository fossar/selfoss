<?php

namespace Tests\Spouts;

use Dice\Dice;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use helpers\WebClient;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use spouts\youtube\youtube;

final class YouTubeTest extends TestCase {
    /**
     * @dataProvider dataProvider
     *
     * @param string $url
     * @param string $feedTitle
     * @param string $firstItemTitle
     */
    public function testBasic($url, $feedTitle, $firstItemTitle) {
        $cachedFeedPath = __DIR__ . '/resources/YouTube/' . str_replace([':', '/', '?', '='], '_', $url) . '.xml';

        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/rss+xml'], file_get_contents($cachedFeedPath)),
        ]);
        $stack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $stack]);

        $dice = new Dice();
        $dice = $dice->addRule(Logger::class, [
            'shared' => true,
            'constructParams' => ['selfoss'],
        ]);
        $dice = $dice->addRule('*', [
            'substitutions' => [
                WebClient::class => [
                    Dice::INSTANCE => function() use ($httpClient) {
                        $stub = $this->createMock(WebClient::class);
                        $stub->method('getHttpClient')->willReturn($httpClient);

                        return $stub;
                    },
                ],
            ],
        ]);

        $yt = $dice->create(youtube::class);

        $params = [
            'channel' => $url,
        ];

        $yt->load($params);

        // Uncomment the following line to refresh the resources:
        // file_put_contents($cachedFeedPath, file_get_contents($yt->getXmlUrl($params)));

        $this->assertEquals($feedTitle, $yt->getTitle());
        $this->assertEquals($firstItemTitle, $yt->getItems()->current()->getTitle());
    }

    public function dataProvider() {
        return [
            [
                'url' => 'https://www.youtube.com/user/ZoggFromBetelgeuse',
                'feedTitle' => 'Zogg from Betelgeuse',
                'firstItemTitle' => 'Earthlings 101 - Channel Ad',
            ],
            [
                'url' => 'https://www.youtube.com/channel/UCKY00CSQo1MoC27bdGd-w_g',
                'feedTitle' => 'Zogg from Betelgeuse',
                'firstItemTitle' => 'Earthlings 101 - Channel Ad',
            ],
            [
                'url' => 'https://www.youtube.com/ZoggFromBetelgeuse',
                'feedTitle' => 'Zogg from Betelgeuse',
                'firstItemTitle' => 'Earthlings 101 - Channel Ad',
            ],
            [
                'url' => 'ZoggFromBetelgeuse',
                'feedTitle' => 'Zogg from Betelgeuse',
                'firstItemTitle' => 'Earthlings 101 - Channel Ad',
            ],
            [
                'url' => 'https://www.youtube.com/playlist?list=PLKhDkilF5o6_pFucn5JHd6xy7muHLK6pS',
                'feedTitle' => 'BeeKeeping',
                'firstItemTitle' => 'Year of BeeKeeping Episode 15, Finding Queen',
            ],
        ];
    }
}
