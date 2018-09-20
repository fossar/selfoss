<?php

namespace spouts\rss;

/**
 * Spout for fetching an rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class feed extends \spouts\spout {
    /** @var string name of source */
    public $name = 'RSS Feed';

    /** @var string description of this source type */
    public $description = 'An default RSS Feed as source';

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
        'url' => [
            'title' => 'URL',
            'type' => 'url',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ]
    ];

    /** @var array|bool current fetched items */
    protected $items = false;

    /** @var string URL of the source */
    protected $htmlUrl = '';

    /** @var string URL of the favicon */
    protected $faviconUrl = null;

    //
    // Iterator Interface
    //

    /**
     * reset iterator
     *
     * @return void
     */
    public function rewind() {
        if ($this->items !== false) {
            reset($this->items);
        }
    }

    /**
     * receive current item
     *
     * @return \SimplePie_Item current item
     */
    public function current() {
        if ($this->items !== false) {
            return $this;
        }

        return false;
    }

    /**
     * receive key of current item
     *
     * @return mixed key of current item
     */
    public function key() {
        if ($this->items !== false) {
            return key($this->items);
        }

        return false;
    }

    /**
     * select next item
     *
     * @return \SimplePie_Item next item
     */
    public function next() {
        if ($this->items !== false) {
            next($this->items);
        }

        return $this;
    }

    /**
     * end reached
     *
     * @return bool false if end reached
     */
    public function valid() {
        if ($this->items !== false) {
            return current($this->items) !== false;
        }

        return false;
    }

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
        // initialize simplepie feed loader
        $this->feed = @new \SimplePie();
        @$this->feed->set_cache_location(\F3::get('cache'));
        @$this->feed->set_cache_duration(1800);
        @$this->feed->set_file_class('\helpers\SimplePieFileGuzzle');
        @$this->feed->set_feed_url(htmlspecialchars_decode($params['url']));
        @$this->feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_AUTODISCOVERY | SIMPLEPIE_LOCATOR_LOCAL_EXTENSION | SIMPLEPIE_LOCATOR_LOCAL_BODY);
        $this->feed->set_useragent(\helpers\WebClient::getUserAgent());

        // fetch items
        @$this->feed->init();

        // on error retry with force_feed
        if (@$this->feed->error()) {
            @$this->feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
            @$this->feed->force_feed(true);
            @$this->feed->init();
        }

        // check for error
        if (@$this->feed->error()) {
            throw new \Exception($this->feed->error());
        } else {
            // save fetched items
            $this->items = @$this->feed->get_items();
        }

        // set html url
        $this->htmlUrl = @$this->feed->get_link();

        $this->spoutTitle = $this->feed->get_title();
    }

    /**
     * returns the xml feed url for the source
     *
     * @param mixed $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl($params) {
        return isset($params['url']) ? html_entity_decode($params['url']) : false;
    }

    /**
     * returns the global html url for the source
     *
     * @return string url as html
     */
    public function getHtmlUrl() {
        if (isset($this->htmlUrl)) {
            return $this->htmlUrl;
        }

        return false;
    }

    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    public function getId() {
        if ($this->items !== false && $this->valid()) {
            $id = @current($this->items)->get_id();
            if (strlen($id) > 255) {
                $id = md5($id);
            }

            return $id;
        }

        return false;
    }

    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getTitle() {
        if ($this->items !== false && $this->valid()) {
            return htmlspecialchars_decode(@current($this->items)->get_title());
        }

        return false;
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if ($this->items !== false && $this->valid()) {
            return @current($this->items)->get_content();
        }

        return false;
    }

    /**
     * returns the icon of this item
     *
     * @return string icon url
     */
    public function getIcon() {
        if ($this->faviconUrl !== null) {
            return $this->faviconUrl;
        }

        $this->faviconUrl = false;
        $imageHelper = $this->getImageHelper();
        $htmlUrl = $this->getHtmlUrl();
        if ($htmlUrl && $imageHelper->fetchFavicon($htmlUrl, true)) {
            $this->faviconUrl = $imageHelper->getFaviconUrl();
            \F3::get('logger')->debug('icon: using feed homepage favicon: ' . $this->faviconUrl);
        } else {
            $feedLogoUrl = $this->feed->get_image_url();
            if ($feedLogoUrl && $imageHelper->fetchFavicon($feedLogoUrl)) {
                $this->faviconUrl = $imageHelper->getFaviconUrl();
                \F3::get('logger')->debug('icon: using feed logo: ' . $this->faviconUrl);
            }
        }

        return $this->faviconUrl;
    }

    /**
     * returns the link of this item
     *
     * @return string link
     */
    public function getLink() {
        if ($this->items !== false && $this->valid()) {
            $link = @current($this->items)->get_link();

            return $link;
        }

        return false;
    }

    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getDate() {
        if ($this->items !== false && $this->valid()) {
            $date = @current($this->items)->get_date('Y-m-d H:i:s');
        }
        if (strlen($date) == 0) {
            $date = date('Y-m-d H:i:s');
        }

        return $date;
    }

    /**
     * returns the author of this item
     *
     * @return string author
     */
    public function getAuthor() {
        if ($this->items !== false && $this->valid()) {
            $author = @current($this->items)->get_author();
            if (isset($author)) {
                $name = $author->get_name();
                if (isset($name)) {
                    return htmlspecialchars_decode($name);
                } else {
                    return htmlspecialchars_decode($author->get_email());
                }
            }
        }

        return null;
    }

    /**
     * destroy the plugin (prevent memory issues)
     */
    public function destroy() {
        $this->feed->__destruct();
        unset($this->items);
        $this->items = false;
    }
}
