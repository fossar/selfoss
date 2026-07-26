<?php

declare(strict_types=1);

namespace Tests\Spouts;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Selfoss\helpers\HtmlString;
use Slince\Di\Container;
use spouts\rss\enclosures;

final class RssEnclosuresTest extends TestCase {
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

        $container = new Container();
        $container->setDefaults(['shared' => false]);

        $container
            ->register(Logger::class)
            ->setArgument('name', 'selfoss')
            ->setShared(true)
        ;
        $container
            ->register(ClientInterface::class, $httpClient);

        $yt = $container->get(enclosures::class);

        $params = [
            'url' => $urls[0]['url'],
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
        yield [
            'urls' => [
                self::makeRemoteFile('https://escapepod.org/feed/podcast/', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Escape Pod',
            'firstItemTitle' => HtmlString::fromPlainText('Escape Pod 1055: Impossibility Crow (Flashback Friday)'),
            'firstItemContent' => HtmlString::fromRaw(
                <<<HTML
                    Author : Remy Nakamura Narrator : Roberto Suarez Hosts : Valerie Valdes and Alasdair Stuart Audio Producer : Adam Pracht Impossibility Crow was originally published as Escape Pod episode 557 on January 5, 2017. Includes violence and swears. Impossibility Crow By Remy Nakamura The Kingdom Coffee Missionary Handbook tells Paulo that he should always put […]
                    <p><a href="https://escapepod.org/2026/07/23/escape-pod-1055-impossibility-crow-flashback-friday/" rel="nofollow">Source</a></p>
                    <a href="https://dts.podtrac.com/redirect.mp3/traffic.libsyn.com/escapepod/Escape_Pod_1055_Impossibility_Crow_Flashback_Friday.mp3">Download (34:30)</a>
                    HTML
            ),
        ];

        yield [
            'urls' => [
                self::makeRemoteFile('https://rss.art19.com/humans', 'application/rss+xml', '.xml'),
            ],
            'feedTitle' => 'Humans',
            'firstItemTitle' => HtmlString::fromPlainText('Vicky Holmes: Finding Humanity in Feral Cats'),
            'firstItemContent' => HtmlString::fromRaw(
                <<<HTML
                    <p>The original author of the Warrior Cats book series has contributed to over 70 books about feral felines, all while not liking cats. Hank and Vicky discuss following your “still, small voice of calm,” the delicacy and respect with which she approaches her young audience, and how the series is autobiographical.\u{a0}</p><p>Got a question or a comment? You can reach us at humans@hankgreen.com</p><p>Find us on social media @humanswithhank</p>
                    <a href="https://rss.art19.com/episodes/9a4c20f1-a792-4b54-81b7-51bef5708bef.mp3?rss_browser=BAhJIgljdXJsBjoGRVQ%3D--435795d5c850773aaa4739d968bd77a1dfd6f301">Download (1:04:13)</a>
                    HTML
            ),
        ];
    }

    public static function getResourcePath(string $url): string {
        $fileName = str_replace([':', '/', '?', '=', '@'], '_', $url);

        return __DIR__ . '/resources/Feed/' . $fileName;
    }

    /**
     * @return array{url: string, fileName: string, contentType: string}
     */
    public static function makeRemoteFile(string $url, string $contentType, string $extension = ''): array {
        return [
            'url' => $url,
            'fileName' => self::getResourcePath($url) . $extension,
            'contentType' => $contentType,
        ];
    }
}
