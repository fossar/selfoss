<?php

namespace controllers;

use Base;
use FeedWriter\RSS2;
use helpers\View;

/**
 * Controller for rss access
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Rss {
    /** @var View view helper */
    private $view;

    public function __construct(View $view) {
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
        \F3::get('auth')->needsLoggedInOrPublicMode();

        $feedWriter = new RSS2();
        $feedWriter->setTitle(\F3::get('rss_title'));
        $feedWriter->setChannelElement('description', '');
        $feedWriter->setSelfLink($this->view->base . 'feed');

        $feedWriter->setLink($this->view->base);

        // get sources
        $sourceDao = new \daos\Sources();
        $lastSourceId = 0;
        $lastSourceName = '';

        // set options
        $options = [];
        if (count($_GET) > 0) {
            $options = $_GET;
        }
        $options['items'] = \F3::get('rss_max_items');
        if (isset($params['tag'])) {
            $options['tag'] = $params['tag'];
        }
        if (isset($params['type'])) {
            $options['type'] = $params['type'];
        }

        // get items
        $newestEntryDate = null;
        $lastid = null;
        $itemDao = new \daos\Items();
        foreach ($itemDao->get($options) as $item) {
            if ($newestEntryDate === null) {
                $newestEntryDate = $item['datetime'];
            }
            $newItem = $feedWriter->createNewItem();

            // get Source Name
            if ($item['source'] != $lastSourceId) {
                foreach ($sourceDao->get() as $source) {
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

            $feedWriter->addItem($newItem);
            $lastid = $item['id'];

            // mark as read
            if (\F3::get('rss_mark_as_read') == 1 && $lastid !== null) {
                $itemDao->mark($lastid);
            }
        }

        if ($newestEntryDate === null) {
            $newestEntryDate = date(\DATE_ATOM, time());
        }
        $feedWriter->setDate($newestEntryDate);

        $feedWriter->printFeed();
    }

    private function sanitizeTitle($title) {
        $title = strip_tags($title);
        $title = html_entity_decode($title, ENT_HTML5, 'UTF-8');

        return $title;
    }
}
