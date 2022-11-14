<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news from heise with the full text
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class heise extends fulltextrss {
    /** @var string name of spout */
    public $name = '[German] heise.de';

    /** @var string description of this source type */
    public $description = 'Fetch the heise news with full content (not only the header as content).';

    /** @var array configurable parameters */
    public $params = [
        'section' => [
            'title' => 'Section',
            'type' => 'select',
            'values' => [
                'main' => 'Hauptseite',
                'ct' => "c't",
                'ix' => 'iX',
                'tr' => 'Technology Review',
                'mac' => 'Mac &amp; i',
                'mobil' => 'mobil',
                'sec' => 'Security',
                'net' => 'Netze',
                'open' => 'Open Source',
                'dev' => 'Developer',
                'tp' => 'Telepolis',
                'resale' => 'Resale',
                'foto' => 'Foto',
                'autos' => 'Autos',
                'hh' => 'Hardware-Hacks',
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
        'main' => 'https://www.heise.de/newsticker/heise-atom.xml',
        'ct' => 'https://www.heise.de/ct/rss/artikel-atom.xml',
        'ix' => 'https://www.heise.de/ix/news/news-atom.xml',
        'tr' => 'https://www.heise.de/tr/news-atom.xml',
        'mac' => 'https://www.heise.de/mac-and-i/news-atom.xml',
        'mobil' => 'https://www.heise.de/mobil/newsticker/heise-atom.xml',
        'sec' => 'https://www.heise.de/security/news/news-atom.xml',
        'net' => 'https://www.heise.de/netze/rss/netze-atom.xml',
        'open' => 'https://www.heise.de/open/news/news-atom.xml',
        'dev' => 'https://www.heise.de/developer/rss/news-atom.xml',
        'tp' => 'https://www.heise.de/tp/news-atom.xml',
        'resale' => 'https://www.heise.de/resale/rss/resale-atom.xml',
        'foto' => 'https://www.heise.de/foto/rss/news-atom.xml',
        'autos' => 'https://www.heise.de/autos/rss/news-atom.xml',
        'hh' => 'https://www.heise.de/hardware-hacks/rss/hardware-hacks-atom.xml',
    ];

    public function load(array $params): void {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    public function getXmlUrl(array $params): string {
        return self::FEED_URLS[$params['section']];
    }
}
