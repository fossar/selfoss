<?php

declare(strict_types=1);

namespace controllers;

use daos\ItemOptions;
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
    private Authentication $authentication;
    private Configuration $configuration;
    private RSS2 $feedWriter;
    private \daos\Items $itemsDao;
    private \daos\Sources $sourcesDao;
    private View $view;

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
     */
    public function rss(): void {
        $this->authentication->ensureCanRead();

        $this->feedWriter->setTitle($this->configuration->rssTitle);
        $this->feedWriter->setChannelElement('description', '');
        $this->feedWriter->setSelfLink($this->view->getBaseUrl() . 'feed');

        $this->feedWriter->setLink($this->view->getBaseUrl());

        // get sources
        $lastSourceId = 0;
        $lastSourceName = '';

        $options = new ItemOptions($_GET);

        // get items
        $newestEntryDate = null;
        $itemsToMark = [];
        foreach ($this->itemsDao->get($options) as $item) {
            if ($newestEntryDate === null) {
                $newestEntryDate = $item['datetime'];
            }
            $newItem = $this->feedWriter->createNewItem();

            // get Source Name
            if ($item['source'] != $lastSourceId) {
                foreach ($this->sourcesDao->getAll() as $source) {
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
            $itemsToMark[] = $item['id'];
        }

        // mark as read
        if ($this->configuration->rssMarkAsRead && count($itemsToMark) > 0) {
            $this->itemsDao->mark($itemsToMark);
        }

        if ($newestEntryDate === null) {
            $newestEntryDate = new \DateTime();
        }
        $this->feedWriter->setDate($newestEntryDate);

        $this->feedWriter->printFeed();
    }

    private function sanitizeTitle(string $title): string {
        $title = strip_tags($title);
        $title = html_entity_decode($title, ENT_HTML5, 'UTF-8');

        return $title;
    }
}
