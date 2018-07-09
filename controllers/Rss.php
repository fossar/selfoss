<?php

namespace controllers;

use FeedWriter\RSS2;

/**
 * Controller for rss access
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Rss extends BaseController {
    /**
     * rss feed
     *
     * @return void
     */
    public function rss() {
        $this->needsLoggedInOrPublicMode();

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
        if (\F3::get('PARAMS["tag"]') != null) {
            $options['tag'] = \F3::get('PARAMS["tag"]');
        }
        if (\F3::get('PARAMS["type"]') != null) {
            $options['type'] = \F3::get('PARAMS["type"]');
        }

        // get items
        $newestEntryDate = false;
        $lastid = -1;
        $itemDao = new \daos\Items();
        foreach ($itemDao->get($options) as $item) {
            if ($newestEntryDate === false) {
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
            if (\F3::get('rss_mark_as_read') == 1 && $lastid != -1) {
                $itemDao->mark($lastid);
            }
        }

        if ($newestEntryDate === false) {
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
