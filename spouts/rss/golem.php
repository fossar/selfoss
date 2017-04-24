<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news from golem with the full text
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class golem extends feed {
    /** @var string name of spout */
    public $name = '[German] golem.de';

    /** @var string description of this source type */
    public $description = 'Fetch the golem news with full content (not only the header as content).';

    /** @var array configurable parameters */
    public $params = [
        'section' => [
            'title' => 'Section',
            'type' => 'select',
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
                'wirtschaft' => 'Wirtschaft',
                'ecommerce' => 'E-Commerce',
                'forum' => 'Forumsbeiträge'
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
        'wirtschaft' => 'https://rss.golem.de/rss.php?r=wi&feed=RSS2.0',
        'ecommerce' => 'https://rss.golem.de/rss.php?r=ec&feed=RSS2.0',
        'forum' => 'https://forum.golem.de/rss.php?feed=RSS2.0'
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
            $originalContent = $this->cleanContent(file_get_contents($this->getLink()));
            preg_match_all('|<!--content-->(.*?)<!--/content-->|ims', $originalContent, $matches, PREG_PATTERN_ORDER);
            if (is_array($matches) && is_array($matches[0]) && isset($matches[0][0])) {
                return $matches[0][0];
            }
        }

        return parent::getContent();
    }

    /**
     * clean the content
     *
     * @param string $content original content
     *
     * @return string cleaned content
     */
    private function cleanContent($content) {
        $content = preg_replace('|<!-- begin ad tag \(tile=4\) -->(.*?)<!-- end ad tag \(tile=4\) -->|ims', '', $content);
        $content = preg_replace('|<figure id="([^"]+)"></figure>|ims', '', $content);
        $content = preg_replace('|<a class="golem-gallery2-nojs" href="([^"]+)">(.*?)<img src="([^"]+)" alt="([^"]+)" title="([^"]+)" data-src="([^"]+)" data-src-full="([^"]+)">(.*?)</a>|ims', '<p><a href="$1" target="_blank" rel="noopener noreferrer"><img src="$3" alt="$4" title="$5" /></a></p>', $content);

        return $content;
    }
}
