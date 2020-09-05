<?php

namespace controllers;

use Base;
use helpers\Authentication;
use helpers\View;

/**
 * Controller for instance information API
 */
class About {
    /** @var Authentication authentication helper */
    private $authentication;

    /** @var View view helper */
    private $view;

    public function __construct(Authentication $authentication, View $view) {
        $this->authentication = $authentication;
        $this->view = $view;
    }

    /**
     * Provide information about the selfoss instance.
     * json
     *
     * @return void
     */
    public function about(Base $f3) {
        $anonymizer = \helpers\Anonymizer::getAnonymizer();
        $wallabag = !empty($f3->get('wallabag')) ? [
            'url' => $f3->get('wallabag'), // string
            'version' => $f3->get('wallabag_version'), // int
        ] : null;

        $configuration = [
            'version' => $f3->get('version'),
            'apiversion' => $f3->get('apiversion'),
            'configuration' => [
                'homepage' => $f3->get('homepage') ? $f3->get('homepage') : 'newest', // string
                'anonymizer' => $anonymizer === '' ? null : $anonymizer, // ?string
                'share' => (string) $f3->get('share'), // string
                'wallabag' => $wallabag, // ?array
                'wordpress' => $f3->get('wordpress'), // ?string
                'autoMarkAsRead' => $f3->get('auto_mark_as_read') == 1, // bool
                'autoCollapse' => $f3->get('auto_collapse') == 1, // bool
                'autoStreamMore' => $f3->get('auto_stream_more') == 1, // bool
                'loadImagesOnMobile' => $f3->get('load_images_on_mobile') == 1, // bool
                'itemsPerPage' => $f3->get('items_perpage'), // int
                'unreadOrder' => $f3->get('unread_order'), // string
                'autoHideReadOnMobile' => $f3->get('auto_hide_read_on_mobile') == 1, // bool
                'scrollToArticleHeader' => $f3->get('scroll_to_article_header') == 1, // bool
                'showThumbnails' => $f3->get('show_thumbnails') == 1, // bool
                'htmlTitle' => trim($f3->get('html_title')), // string
                'allowPublicUpdate' => $f3->get('allow_public_update_access') == 1, // bool
                'publicMode' => $f3->get('public') == 1, // bool
                'authEnabled' => $this->authentication->enabled() === true, // bool
                'readingSpeed' => $f3->get('reading_speed_wpm') > 0 ? (int) $f3->get('reading_speed_wpm') : null, // ?int
                'language' => $f3->get('language') === 0 ? null : $f3->get('language'), // ?string
                'userCss' => file_exists(BASEDIR . '/user.css') ? filemtime(BASEDIR . '/user.css') : null, // ?int
                'userJs' => file_exists(BASEDIR . '/user.js') ? filemtime(BASEDIR . '/user.js') : null, // ?int
            ],
        ];

        $this->view->jsonSuccess($configuration);
    }
}
