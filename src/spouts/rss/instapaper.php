<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news and cleaning the content with instapaper.com
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class instapaper extends feed {
    /** @var string name of spout */
    public $name = 'RSS Feed (with instapaper)';

    /** @var string description of this source type */
    public $description = 'Get feed and clean the content with instapaper.com service.';

    /** @var array configurable parameters */
    public $params = [
        'url' => [
            'title' => 'URL',
            'type' => 'url',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ]
    ];

    /**
     * loads content for given source
     *
     * @param array $params
     *
     * @return void
     */
    public function load(array $params) {
        parent::load(['url' => $params['url']]);
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        $contentFromInstapaper = $this->fetchFromInstapaper(parent::getLink());
        if ($contentFromInstapaper === null) {
            return 'instapaper parse error <br />' . parent::getContent();
        }

        return $contentFromInstapaper;
    }

    /**
     * fetch content from instapaper.com
     *
     * @author janeczku @github
     *
     * @param string $url
     *
     * @return string content
     */
    private function fetchFromInstapaper($url) {
        if (function_exists('curl_init') && !ini_get('open_basedir')) {
            $content = $this->file_get_contents_curl('https://www.instapaper.com/text?u=' . urlencode($url));
        } else {
            $content = @file_get_contents('https://www.instapaper.com/text?u=' . urlencode($url));
        }
        $dom = new \DOMDocument();
        @$dom->loadHTML($content);
        if (!$dom) {
            return null;
        }
        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query("//div[@id='story']");
        $content = $dom->saveXML($elements->item(0), LIBXML_NOEMPTYTAG);

        return $content;
    }

    private function file_get_contents_curl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = @curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}
