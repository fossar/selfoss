<?php

namespace spouts\facebook;

/**
 * Spout for fetching a facebook page feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Thomas Muguet <t.muguet@thomasmuguet.info>
 */
class page extends \spouts\rss\feed {
    /** @var string name of source */
    public $name = 'Facebook page feed';

    /** @var string description of this source type */
    public $description = 'Page wall';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
     *
     * e.g.
     * array(
     *   "id" => array(
     *     "title"      => "URL",
     *     "type"       => "text",
     *     "default"    => "",
     *     "required"   => true,
     *     "validation" => array("alnum")
     *   ),
     *   ....
     * )
     *
     * @var bool|mixed
     */
    public $params = [
        'user' => [
            'title' => 'Page name',
            'type' => 'text',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ]
    ];

    //
    // Source Methods
    //

    /**
     * loads content for given source
     * I supress all Warnings of SimplePie for ensuring
     * working plugin in PHP Strict mode
     *
     * @param mixed $params the params of this source
     *
     * @return void
     */
    public function load($params) {
        parent::load(['url' => $this->getXmlUrl($params)]);
    }

    /**
     * returns the xml feed url for the source
     *
     * @param mixed $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl($params) {
        $protocol = 'http://';
        if (version_compare(PHP_VERSION, '5.3.0') >= 0 && defined('OPENSSL_VERSION_NUMBER')) {
            $protocol = 'https://';
        }
        $content = @file_get_contents($protocol . 'graph.facebook.com/' . urlencode($params['user']));
        $data = json_decode($content, true);

        return $protocol . 'www.facebook.com/feeds/page.php?format=atom10&id=' . $data['id'];
    }
}
