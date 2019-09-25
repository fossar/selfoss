<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news from pro-linux with the full text.
 * Based on heise.php
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 * @author     Sebastian Gibb <mail@sebastiangibb.de>
 */
class prolinux extends fulltextrss {
    /** @var string name of spout */
    public $name = '[German] pro-linux.de';

    /** @var string description of this source type */
    public $description = 'Fetch the pro-linux news with full content (not only the header as content).';

    /** @var array configurable parameters */
    public $params = [
        'section' => [
            'title' => 'Section',
            'type' => 'select',
            'values' => [
                'main' => 'Alles',
                'news' => 'Nachrichten/Artikel',
                'polls' => 'Umfragen',
                'security' => 'Sicherheitsmeldungen',
                'lugs' => 'Linux User Groups (LUGs)',
                'comments' => 'Kommentare'
            ],
            'default' => 'main',
            'required' => true,
            'validation' => []
        ]
    ];

    /**
     * addresses of feeds for the sections
     */
    private $feedUrls = [
        'main' => 'http://www.pro-linux.de/NB3/rss/1/4/atom_alles.xml',
        'news' => 'http://www.pro-linux.de/NB3/rss/2/4/atom_aktuell.xml',
        'polls' => 'http://www.pro-linux.de/NB3/rss/3/4/atom_umfragen.xml',
        'security' => 'http://www.pro-linux.de/NB3/rss/5/4/atom_sicherheit.xml',
        'lugs' => 'http://www.pro-linux.de/rss/7/4/atom_lugs.xml',
        'comments' => 'http://www.pro-linux.de/NB3/rss/6/4/atom_kommentare.xml'
    ];

    /**
     * loads content for given source
     *
     * @param string $url
     * @param array $params
     *
     * @return void
     */
    public function load(array $params) {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    /**
     * returns the xml feed url for the source
     *
     * @param array $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl(array $params) {
        return $this->feedUrls[$params['section']];
    }
}
