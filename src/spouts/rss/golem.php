<?php

declare(strict_types=1);

namespace spouts\rss;

use spouts\Parameter;

/**
 * Plugin for fetching the news from golem with the full text
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class golem extends fulltextrss {
    public string $name = '[German] golem.de';

    public string $description = 'Fetch the golem news with full content (not only the header as content).';

    public array $params = [
        'section' => [
            'title' => 'Section',
            'type' => Parameter::TYPE_SELECT,
            'values' => [
                'main' => 'All',
                'audiovideo' => 'Audio/Video',
                'foto' => 'Foto',
                'games' => 'Games',
                'handy' => 'Handy',
                'internet' => 'Internet',
                'mobil' => 'Mobil',
                'oss' => 'OSS',
                'politik' => 'Politik/Recht',
                'security' => 'Security',
                'desktop' => 'Desktop-Applikationen',
                'se' => 'Software-Entwicklung',
                'wirtschaft' => 'Wirtschaft',
                'hardware' => 'Hardware',
                'software' => 'Software',
                'networld' => 'Networld',
                'entertainment' => 'Entertainment',
                'tk' => 'TK',
                'ecommerce' => 'E-Commerce',
                'forum' => 'Forumsbeiträge',
            ],
            'default' => 'main',
            'required' => true,
            'validation' => [],
        ],
    ];

    /**
     * addresses of feeds for the sections
     */
    private const FEED_URLS = [
        'main' => 'https://rss.golem.de/rss.php?feed=RSS2.0',
        'audiovideo' => 'https://rss.golem.de/rss.php?tp=av&feed=RSS2.0',
        'foto' => 'https://rss.golem.de/rss.php?tp=foto&feed=RSS2.0',
        'games' => 'https://rss.golem.de/rss.php?tp=games&feed=RSS2.0',
        'handy' => 'https://rss.golem.de/rss.php?tp=handy&feed=RSS2.0',
        'internet' => 'https://rss.golem.de/rss.php?tp=inet&feed=ATOM1.0',
        'mobil' => 'https://rss.golem.de/rss.php?tp=mc&feed=RSS2.0',
        'oss' => 'https://rss.golem.de/rss.php?tp=oss&feed=RSS2.0',
        'politik' => 'https://rss.golem.de/rss.php?tp=pol&feed=RSS2.0',
        'security' => 'https://rss.golem.de/rss.php?tp=sec&feed=RSS2.0',
        'desktop' => 'https://rss.golem.de/rss.php?tp=apps&feed=RSS2.0',
        'se' => 'https://rss.golem.de/rss.php?tp=dev&feed=RSS2.0',
        'wirtschaft' => 'https://rss.golem.de/rss.php?tp=wirtschaft&feed=RSS2.0',
        'hardware' => 'https://rss.golem.de/rss.php?r=hw&feed=RSS2.0',
        'software' => 'https://rss.golem.de/rss.php?r=sw&feed=RSS2.0',
        'networld' => 'https://rss.golem.de/rss.php?r=nw&feed=RSS2.0',
        'entertainment' => 'https://rss.golem.de/rss.php?r=et&feed=RSS2.0',
        'tk' => 'https://rss.golem.de/rss.php?r=tk&feed=RSS2.0',
        'ecommerce' => 'https://rss.golem.de/rss.php?r=ec&feed=RSS2.0',
        'forum' => 'https://forum.golem.de/rss.php?feed=RSS2.0',
    ];

    public function load(array $params): void {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params): string {
        return self::FEED_URLS[$params['section']];
    }
}
