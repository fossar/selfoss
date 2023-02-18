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
    public function testBasic(array $urls, string $feedTitle, HtmlString $firstItemTitle, HtmlString $firstItemContent): void {
        $mock = new MockHandler(
            array_map(
                function(array $remoteFile): Response {
                    ['fileName' => $fileName, 'contentType' => $contentType] = $remoteFile;

                    return new Response(200, ['Content-Type' => $contentType], @file_get_contents($fileName) ?: "Unable to open  {$fileName}.");
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
        $this->assertEquals($firstItemContent, $yt->getItems()->current()->getContent());
    }

    /**
     * @return Generator<array{urls: array{url: string, fileName: string, contentType: string}[], feedTitle: string, firstItemTitle: HtmlString, firstItemContent: HtmlString}>
     */
    public function dataProvider(): Generator {
        $zoggContent = HtmlString::fromRaw(<<<HTML
This is the third and last part of a three-part miniseries on the shape of the universe. What do we actually know about the shape of the universe?<br>
<br>
Playlist with parts 1, 2 and 3: <a href="https://www.youtube.com/watch?v=_k3_B9Eq7eM&amp;list=PLbqa_PZ3dNahC2jqyu5dxN6I_wqAP2mjc">https://www.youtube.com/watch?v=_k3_B9Eq7eM&amp;list=PLbqa_PZ3dNahC2jqyu5dxN6I_wqAP2mjc</a><br>
<br>
0:00 Introduction<br>
0:22 Intro video<br>
0:53 Curvature and Hyperburritos<br>
2:10 The Big Bang<br>
5:38 Dark Energy and Dark Matter<br>
7:51 The CMB<br>
10:18 Circles in the Sky<br>
11:43 Cosmic Crystallography<br>
12:38 Hot Spots in the CMB<br>
14:51 The Cosmic Drum<br>
16:09 Conclusion<br>
16:37 Ad: Smooth Space<br>
17:32 Alien Cosmology<br>
18:48 Thanks<br>
19:03 The End<br>
<br>
<br>
Links<br>
- Concept, artwork and text by Martin Kuppe<br>
- Title music by OcularNebula (<a href="https://www.newgrounds.com/audio/listen/381971">https://www.newgrounds.com/audio/listen/381971</a>)<br>
- Other music and sound effects from <a href="http://Artlist.io">Artlist.io</a><br>
- An article which gives an excellent overview of what we know: <a href="https://www.mdpi.com/2218-1997/2/1/1">https://www.mdpi.com/2218-1997/2/1/1</a>
HTML
        );

        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/user/ZoggFromBetelgeuse', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Zogg from Betelgeuse',
            'firstItemTitle' => HtmlString::fromPlainText('No Edge 3: The Shape of the Universe (What do we know?)'),
            'firstItemContent' => $zoggContent,
        ];

        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/channel/UCKY00CSQo1MoC27bdGd-w_g', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Zogg from Betelgeuse',
            'firstItemTitle' => HtmlString::fromPlainText('No Edge 3: The Shape of the Universe (What do we know?)'),
            'firstItemContent' => $zoggContent,
        ];

        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/ZoggFromBetelgeuse', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Zogg from Betelgeuse',
            'firstItemTitle' => HtmlString::fromPlainText('No Edge 3: The Shape of the Universe (What do we know?)'),
            'firstItemContent' => $zoggContent,
        ];

        yield [
            'urls' => [
                makeRemoteFile('ZoggFromBetelgeuse', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Zogg from Betelgeuse',
            'firstItemTitle' => HtmlString::fromPlainText('No Edge 3: The Shape of the Universe (What do we know?)'),
            'firstItemContent' => $zoggContent,
        ];

        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/playlist?list=PLKhDkilF5o6_pFucn5JHd6xy7muHLK6pS', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'BeeKeeping',
            'firstItemTitle' => HtmlString::fromPlainText('Year of BeeKeeping Episode 15, Finding Queen'),
            'firstItemContent' => HtmlString::fromRaw(<<<HTML
In this episode I find the queen, move her to a nuc box for transport, and put the queen cells I made into the now queenless hive.<br>
<br>
this video originally was over an hour and I cut out half so... I actually should do that more.
HTML
            ),
        ];

        yield [
            'urls' => [
                makeRemoteFile('https://www.youtube.com/@BreakingTaps', 'text/html', '.html'),
                makeRemoteFile('https://www.youtube.com/feeds/videos.xml?channel_id=UC06HVrkOL33D5lLnCPjr6NQ', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Breaking Taps',
            'firstItemTitle' => HtmlString::fromPlainText('Slow Motion Tuning Fork'),
            'firstItemContent' => HtmlString::fromRaw(<<<HTML
Have you ever seen a tuning fork at 10,000 FPS?<br>
<br>
Small clarification: the large fork has a 180hz tone when analyzed with a frequency detector (and held up to your ear), but also higher overtones which is mostly what's heard on the microphone. I should have mentioned the overtones but slipped my mind when filming. Sorry!<br>
<br>
#shorts
HTML
            ),
        ];
    }
}
