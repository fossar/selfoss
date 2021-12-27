<?php

namespace helpers;

use Monolog\Logger;

/**
 * Helper class for authenticate user
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Authentication {
    /** @var Configuration configuration */
    private $configuration;

    /** @var bool loggedin */
    private $loggedin = false;

    /** @var Logger */
    private $logger;

    /**
     * start session and check login
     */
    public function __construct(Configuration $configuration, Logger $logger, View $view) {
        $this->configuration = $configuration;
        $this->logger = $logger;

        if ($this->enabled() === false) {
            return;
        }

        $base_url = parse_url($view->getBaseUrl());

        // session cookie will be valid for one month.
        $cookie_expire = 3600 * 24 * 30;
        $cookie_secure = $base_url['scheme'] === 'https';
        $cookie_httponly = true;
        $cookie_path = $base_url['path'];
        $cookie_domain = $base_url['host'] === 'localhost' ? null : $base_url['host'];

        session_set_cookie_params(
            $cookie_expire, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly
        );
        $this->logger->debug("set cookie on $cookie_domain$cookie_path expiring in $cookie_expire seconds");

        session_name();
        if (session_id() === '') {
            session_start();
        }
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
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
     *
     * @return bool
     */
    public function enabled() {
        return strlen($this->configuration->username) != 0 && strlen($this->configuration->password) != 0;
    }

    /**
     * login user
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function login($username, $password) {
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
                $_SESSION['loggedin'] = true;
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
     *
     * @return bool
     */
    public function isLoggedin() {
        if ($this->enabled() === false) {
            return true;
        }

        return $this->loggedin;
    }

    /**
     * showPrivateTags
     *
     * @return bool
     */
    public function showPrivateTags() {
        return $this->isLoggedin();
    }

    /**
     * logout
     *
     * @return void
     */
    public function logout() {
        $this->loggedin = false;
        $_SESSION['loggedin'] = false;
        session_destroy();
        $this->logger->debug('logged out and destroyed session');
    }

    /**
     * send 403 if not logged in and not public mode
     *
     * @return void
     */
    public function needsLoggedInOrPublicMode() {
        if ($this->isLoggedin() !== true && !$this->configuration->public) {
            $this->forbidden();
        }
    }

    /**
     * send 403 if not logged in
     *
     * @return void
     */
    public function needsLoggedIn() {
        if ($this->isLoggedin() !== true) {
            $this->forbidden();
        }
    }

    /**
     * send 403 if not logged in
     *
     * @return void
     */
    public function forbidden() {
        header('HTTP/1.0 403 Forbidden');
        header('Content-type: text/plain');
        echo 'Access forbidden!';
        exit;
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
        return $this->isLoggedin() === true
            || $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR']
            || $_SERVER['REMOTE_ADDR'] === '127.0.0.1'
            || $this->configuration->allowPublicUpdateAccess;
    }
}
