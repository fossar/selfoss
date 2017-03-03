<?php

namespace helpers;

/**
 * Helper class for authenticate user
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Authentication {
    /** @var bool loggedin */
    private $loggedin = false;

    /**
     * start session and check login
     */
    public function __construct() {
        if ($this->enabled() === false) {
            return;
        }

        $base_url = parse_url(\helpers\View::getBaseUrl());

        // session cookie will be valid for one month.
        $cookie_expire = 3600 * 24 * 30;
        $cookie_secure = $base_url['scheme'] == 'https';
        $cookie_httponly = true;
        $cookie_path = $base_url['path'];
        $cookie_domain = $base_url['host'];

        session_set_cookie_params(
            $cookie_expire, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly
        );
        \F3::get('logger')->debug("set cookie on $cookie_domain$cookie_path expiring in $cookie_expire seconds");

        session_name();
        if (session_id() == '') {
            session_start();
        }
        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
            $this->loggedin = true;
            \F3::get('logger')->debug('logged in using valid session');
        } else {
            \F3::get('logger')->debug('session does not contain valid auth');
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
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function enabled() {
        return strlen(trim(\F3::get('username'))) != 0 && strlen(trim(\F3::get('password'))) != 0;
    }

    /**
     * login user
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     */
    public function loginWithoutUser() {
        $this->loggedin = true;
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
            if (
                $username == \F3::get('username') && hash('sha512', \F3::get('salt') . $password) == \F3::get('password')
            ) {
                $this->loggedin = true;
                $_SESSION['loggedin'] = true;
                \F3::get('logger')->debug('logged in with supplied username and password');

                return true;
            } else {
                \F3::get('logger')->debug('failed to log in with supplied username and password');

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
        \F3::get('logger')->debug('logged out and destroyed session');
    }
}
