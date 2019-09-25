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
class heise extends feed {
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
                'hh' => 'Hardware-Hacks'
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

    /**
     * delimiters of the article text
     *
     * elements: start tag, attribute of start tag, value of start tag attribute, end
     */
    private $textDivs = [
        ['div', 'class', 'meldung_wrapper', '<!-- AUTHOR-DATA-MARKER-BEGIN'], // main, ix, mac, mobil, sec, net, open, dev, resale, foto, hh articles
        ['p', 'class', 'artikel_datum', '<p class="artikel_option">'],        // ct
        ['div', 'class', 'aufmacher', '<!-- AUTHOR-DATA-MARKER-BEGIN'],       // tr
        ['div', 'class', 'datum_autor', '<div class="artikel_fuss">'],        // mac
        ['p', 'class', 'vorlauftext', '<div class="artikel_fuss">'],          // mobil
        ['div', 'id', 'blocon', '</div>'],                                    // tp
        ['div', 'class', 'mar0', '<div id="breadcrumb">'],                    // some tp articles
        ['span', 'class', 'date', '<div xmlns:v="http://rdf'],                // tp
        ['div', 'class', 'artikel_content', '<div class="artikel_fuss">'],    // resale
        ['div', 'id', 'artikel_shortnews', '<p class="editor">'],             // autos
        ['div', 'id', 'projekte', '<div id="artikelfuss">'],                  // hh projects
        ['div', 'id', 'artikel', '<div id="artikelfuss">'],                   // some hh articles
    ];

    /**
     * htmLawed configuration
     */
    private $htmLawedConfig = [
        'abs_url' => 1,
        'base_url' => 'https://www.heise.de/',
        'comment' => 1,
        'safe' => 1,
    ];

    /**
     * loads content for given source
     *
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

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if ($this->items !== null && $this->valid()) {
            $originalContent = file_get_contents($this->getLink());
            foreach ($this->textDivs as $div) {
                $content = $this->getTag($div[1], $div[2], $originalContent, $div[0], $div[3]);
                if (is_array($content) && count($content) >= 1) {
                    return htmLawed($content[0], $this->htmLawedConfig);
                }
            }
        }

        return parent::getContent();
    }

    /**
     * get tag by attribute
     * taken from http://www.catswhocode.com/blog/15-php-regular-expressions-for-web-developers
     *
     * @param string $attr attribute
     * @param string $value necessary value
     * @param string $xml data string
     * @param ?string $tag optional tag
     * @param ?string $end optional ending
     *
     * @return string content
     */
    private function getTag($attr, $value, $xml, $tag = null, $end = null) {
        if ($tag === null) {
            $tag = '\w+';
        } else {
            $tag = preg_quote($tag);
        }

        if ($end === null) {
            $end = '</\1>';
        } else {
            $end = preg_quote($end);
        }

        $attr = preg_quote($attr);
        $value = preg_quote($value);
        $tag_regex = '|<(' . $tag . ')[^>]*' . $attr . '\s*=\s*([\'"])' . $value . '\2[^>]*>(.*?)' . $end . '|ims';
        preg_match_all($tag_regex, $xml, $matches, PREG_PATTERN_ORDER);

        return $matches[3];
    }
}
