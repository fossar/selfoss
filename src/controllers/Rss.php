<?php

namespace controllers;

use Base;
use FeedWriter\RSS2;
use helpers\Authentication;
use helpers\Configuration;
use helpers\View;

/**
 * Controller for rss access
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Rss {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var Configuration configuration */
    private $configuration;

    /** @var RSS2 feed writer */
    private $feedWriter;

    /** @var \daos\Items items */
    private $itemsDao;

    /** @var \daos\Sources sources */
    private $sourcesDao;

    /** @var View view helper */
    private $view;

    public function __construct(Authentication $authentication, Configuration $configuration, RSS2 $feedWriter, \daos\Items $itemsDao, \daos\Sources $sourcesDao, View $view) {
        $this->authentication = $authentication;
        $this->configuration = $configuration;
        $this->feedWriter = $feedWriter;
        $this->itemsDao = $itemsDao;
        $this->sourcesDao = $sourcesDao;
        $this->view = $view;
    }

    /**
     * rss feed
     *
     * @param Base $f3 fatfree base instance
     * @param array $params query string parameters
     *
     * @return void
     */
    public function rss(Base $f3, array $params) {
        $this->authentication->needsLoggedInOrPublicMode();

        $this->feedWriter->setTitle($this->configuration->rssTitle);
        $this->feedWriter->setChannelElement('description', '');
        $this->feedWriter->setSelfLink($this->view->base . 'feed');

        $this->feedWriter->setLink($this->view->base);

        // get sources
        $lastSourceId = 0;
        $lastSourceName = '';

        // set options
        $options = [];
        if (count($_GET) > 0) {
            $options = $_GET;
        }
        $options['items'] = $this->configuration->rssMaxItems;
        if (isset($params['tag'])) {
            $options['tag'] = $params['tag'];
        }
        if (isset($params['type'])) {
            $options['type'] = $params['type'];
        }

        // get items
        $newestEntryDate = null;
        $lastid = null;
        foreach ($this->itemsDao->get($options) as $item) {
            if ($newestEntryDate === null) {
                $newestEntryDate = $item['datetime'];
            }
            $newItem = $this->feedWriter->createNewItem();

            // get Source Name
            if ($item['source'] != $lastSourceId) {
                foreach ($this->sourcesDao->get() as $source) {
                    if ($source['id'] == $item['source']) {
                        $lastSourceId = $source['id'];
                        $lastSourceName = $source['title'];
                        break;
                    }
                }
            }

            $newItem->setTitle($this->sanitizeTitle($item['title'] . ' (' . $lastSourceName . ')'));
            @$newItem->setLink($item['link']);
            @$newItem->setId($item['link']);
            $newItem->setDate($item['datetime']);
            $newItem->setDescription(str_replace('&#34;', '"', $item['content']));

            // add tags in category node
            foreach ($item['tags'] as $tag) {
                $tag = trim($tag);
                if (strlen($tag) > 0) {
                    $newItem->addElement('category', $tag);
                }
            }

            $this->feedWriter->addItem($newItem);
            $lastid = $item['id'];

            // mark as read
            if ($this->configuration->rssMarkAsRead && $lastid !== null) {
                $this->itemsDao->mark($lastid);
            }
        }

        if ($newestEntryDate === null) {
            $newestEntryDate = new \DateTime();
        }
        $this->feedWriter->setDate($newestEntryDate);

        $this->feedWriter->printFeed();
    }

    /**
     * @param string $title
     *
     * @return string
     */
    private function sanitizeTitle($title) {
        $title = strip_tags($title);
        $title = html_entity_decode($title, ENT_HTML5, 'UTF-8');

        return $title;
    }
}
