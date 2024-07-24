<?php

// SPDX-FileCopyrightText: 2011–2016 Tobias Zeising <tobias.zeising@aditu.de>
// SPDX-FileCopyrightText: 2013 zajad <stephan@muehe.de>
// SPDX-FileCopyrightText: 2013 arbk <arbk@aruo.net>
// SPDX-FileCopyrightText: 2014–2017 Alexandre Rossi <alexandre.rossi@gmail.com>
// SPDX-FileCopyrightText: 2016–2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers;

use GuzzleHttp\Psr7\Uri;
use Monolog\Logger;

/**
 * Helper class for session management.
 *
 * This must be the only place to call `session_start()`.
 */
class Session {
    private bool $started = false;

    private Logger $logger;
    private View $view;

    public function __construct(Logger $logger, View $view) {
        $this->logger = $logger;
        $this->view = $view;
    }

    public function start(): void {
        if ($this->started) {
            return;
        }

        $this->started = true;

        $base_url = new Uri($this->view->getBaseUrl());

        // session cookie will be valid for one month.
        $cookie_expire = 3600 * 24 * 30;
        $cookie_secure = $base_url->getScheme() === 'https';
        $cookie_httponly = true;
        $cookie_path = $base_url->getPath();
        $cookie_domain = $base_url->getHost() === 'localhost' ? null : $base_url->getHost();

        session_set_cookie_params(
            $cookie_expire,
            $cookie_path,
            // PHP < 8.0 does not accept null
            $cookie_domain ?? '',
            $cookie_secure,
            $cookie_httponly
        );
        $this->logger->debug("set cookie on $cookie_domain$cookie_path expiring in $cookie_expire seconds");

        session_name();
        if (session_id() === '') {
            session_start();
        }
    }

    public function getString(string $name): ?string {
        $this->start();

        if (array_key_exists($name, $_SESSION) && is_string($_SESSION[$name])) {
            return $_SESSION[$name];
        }

        return null;
    }

    public function setString(string $name, ?string $value): void {
        $this->start();

        $_SESSION[$name] = $value;
    }

    public function destroy(): void {
        if (!$this->started) {
            return;
        }

        session_destroy();
    }
}
