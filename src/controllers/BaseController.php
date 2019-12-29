<?php

namespace controllers;

/**
 * Parent Controller
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class BaseController {
    /**
     * send 403 if not logged in and not public mode
     *
     * @return void
     */
    public function needsLoggedInOrPublicMode() {
        if (\F3::get('auth')->isLoggedin() !== true && \F3::get('public') != 1) {
            \F3::error(403);
        }
    }

    /**
     * send 403 if not logged in
     *
     * @return void
     */
    public function needsLoggedIn() {
        if (\F3::get('auth')->isLoggedin() !== true) {
            \F3::error(403);
        }
    }

    /**
     * Is the user is allowed to update sources?
     *
     * For that, the user either has to be logged in,
     * accessing selfoss from the same computer that it is running on,
     * or public update must be allowed in the config.
     *
     * @return bool
     */
    public function allowedToUpdate() {
        return \F3::get('auth')->isLoggedin() === true
            || $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']
            || $_SERVER['REMOTE_ADDR'] === '127.0.0.1'
            || \F3::get('allow_public_update_access') == 1;
    }
}
