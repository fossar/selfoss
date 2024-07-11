<?php

// SPDX-FileCopyrightText: 2011–2016 Tobias Zeising <tobias.zeising@aditu.de>
// SPDX-FileCopyrightText: 2013 zajad <stephan@muehe.de>
// SPDX-FileCopyrightText: 2013 arbk <arbk@aruo.net>
// SPDX-FileCopyrightText: 2013 yDelouis <ydelouis@gmail.com>
// SPDX-FileCopyrightText: 2014–2017 Alexandre Rossi <alexandre.rossi@gmail.com>
// SPDX-FileCopyrightText: 2016–2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers;

use Monolog\Logger;

/**
 * Helper class for user authentication.
 */
class Authentication {
    private bool $loggedin = false;

    private Configuration $configuration;
    private Logger $logger;
    private Session $session;

    /**
     * start session and check login
     */
    public function __construct(Configuration $configuration, Logger $logger, Session $session) {
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->session = $session;

        if ($this->enabled() === false) {
            return;
        }

        if ($this->session->getBool('loggedin', false)) {
            $this->loggedin = true;
            $this->logger->debug('logged in using valid session');
        } else {
            $this->logger->debug('session does not contain valid auth');
        }

        // autologin if request contains unsername and password
        if ($this->loggedin === false
            && isset($_REQUEST['username'])
            && isset($_REQUEST['password'])) {
            $this->login($_REQUEST['username'], $_REQUEST['password']);
        }
    }

    /**
     * login enabled
     */
    public function enabled(): bool {
        return strlen($this->configuration->username) != 0 && strlen($this->configuration->password) != 0;
    }

    /**
     * login user
     */
    public function login(string $username, string $password): bool {
        if ($this->enabled()) {
            $usernameCorrect = $username === $this->configuration->username;
            $hashedPassword = $this->configuration->password;
            // Passwords hashed with password_hash start with $, otherwise use the legacy path.
            $passwordCorrect =
                $hashedPassword !== '' && $hashedPassword[0] === '$'
                ? password_verify($password, $hashedPassword)
                : hash('sha512', $this->configuration->salt . $password) === $hashedPassword;
            $credentialsCorrect = $usernameCorrect && $passwordCorrect;

            if ($credentialsCorrect) {
                $this->loggedin = true;
                $this->session->setBool('loggedin', true);
                $this->logger->debug('logged in with supplied username and password');

                return true;
            } else {
                $this->logger->debug('failed to log in with supplied username and password');

                return false;
            }
        }

        return true;
    }

    /**
     * isloggedin
     */
    public function isLoggedin(): bool {
        if ($this->enabled() === false) {
            return true;
        }

        return $this->loggedin;
    }

    /**
     * showPrivateTags
     */
    public function showPrivateTags(): bool {
        return $this->isLoggedin();
    }

    /**
     * logout
     */
    public function logout(): void {
        $this->loggedin = false;
        $this->session->setBool('loggedin', false);
        $this->session->destroy();
        $this->logger->debug('logged out and destroyed session');
    }

    /**
     * send 403 if not logged in and not public mode
     */
    public function needsLoggedInOrPublicMode(): void {
        if ($this->isLoggedin() !== true && !$this->configuration->public) {
            $this->forbidden();
        }
    }

    /**
     * send 403 if not logged in
     */
    public function needsLoggedIn(): void {
        if ($this->isLoggedin() !== true) {
            $this->forbidden();
        }
    }

    /**
     * send 403 if not logged in
     */
    public function forbidden(): void {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access forbidden!';
        exit;
    }

    /**
     * Is the user is allowed to update sources?
     *
     * For that, the user either has to be logged in,
     * accessing selfoss from the same computer that it is running on,
     * or public update must be allowed in the config.
     */
    public function allowedToUpdate(): bool {
        return $this->isLoggedin() === true
            || $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']
            || $_SERVER['REMOTE_ADDR'] === '127.0.0.1'
            || $this->configuration->allowPublicUpdateAccess;
    }
}
