<?php

declare(strict_types=1);

namespace Tests\Spouts;

use Dice\Dice;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use helpers\HtmlString;
use helpers\WebClient;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use spouts\youtube\youtube;

function getResourcePath(string $url): string {
    $fileName = str_replace([':', '/', '?', '=', '@'], '_', $url);

    return __DIR__ . '/resources/YouTube/' . $fileName;
}

/**
 * @return array{url: string, fileName: string, contentType: string}
 */
function makeRemoteFile(string $url, string $contentType, string $extension = ''): array {
    return [
        'url' => $url,
        'fileName' => getResourcePath($url) . $extension,
        'contentType' => $contentType,
    ];
}

final class YouTubeTest extends TestCase {
    /**
     * @dataProvider dataProvider
     *
     * @param array{url: string, fileName: string, contentType: string}[] $urls
     */
    public function testBasic(array $urls, string $feedTitle, HtmlString $firstItemTitle): void {
        $mock = new MockHandler(
            array_map(
                function(array $remoteFile): Response {
                    ['fileName' => $fileName, 'contentType' => $contentType] = $remoteFile;

                    return new Response(200, ['Content-Type' => $contentType], file_get_contents($fileName));
                },
                $urls
            )
        );
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
            'channel' => $urls[0]['url'],
        ];

        $yt->load($params);

        $this->assertEquals($feedTitle, $yt->getTitle());
        $this->assertEquals($firstItemTitle, $yt->getItems()->current()->getTitle());
    }

    /**
     * @return Generator<array{urls: array{url: string, fileName: string, contentType: string}[], feedTitle: string, firstItemTitle: HtmlString}>
     */
    public function dataProvider(): Generator {
        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/user/ZoggFromBetelgeuse', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Zogg from Betelgeuse',
            'firstItemTitle' => HtmlString::fromPlainText('No Edge 3: The Shape of the Universe (What do we know?)'),
        ];

        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/channel/UCKY00CSQo1MoC27bdGd-w_g', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Zogg from Betelgeuse',
            'firstItemTitle' => HtmlString::fromPlainText('No Edge 3: The Shape of the Universe (What do we know?)'),
        ];

        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/ZoggFromBetelgeuse', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Zogg from Betelgeuse',
            'firstItemTitle' => HtmlString::fromPlainText('No Edge 3: The Shape of the Universe (What do we know?)'),
        ];

        yield [
            'urls' => [
                makeRemoteFile('ZoggFromBetelgeuse', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Zogg from Betelgeuse',
            'firstItemTitle' => HtmlString::fromPlainText('No Edge 3: The Shape of the Universe (What do we know?)'),
        ];

        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/playlist?list=PLKhDkilF5o6_pFucn5JHd6xy7muHLK6pS', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'BeeKeeping',
            'firstItemTitle' => HtmlString::fromPlainText('Year of BeeKeeping Episode 15, Finding Queen'),
        ];

        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/@BreakingTaps', 'text/html', '.html'),
                makeRemoteFile('https://www.youtube.com/feeds/videos.xml?channel_id=UC06HVrkOL33D5lLnCPjr6NQ', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Breaking Taps',
            'firstItemTitle' => HtmlString::fromPlainText('Slow Motion Tuning Fork'),
        ];
    }
}
