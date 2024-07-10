<?php

// SPDX-FileCopyrightText: 2024 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Authentication\Services;

use helpers\Authentication\AuthenticationService;
use helpers\Configuration;
use helpers\Session;
use Monolog\Logger;

/**
 * Authentication method that verifies credentials given using
 * the following means against those specified in configuration file:
 *
 *  - `username` and `password` in `POST` data
 *  - `username` and `password` in `GET` query string
 *
 * Additionally, it will persist the authorization in a session.
 */
final class RequestOrSession implements AuthenticationService {
    private const SESSION_KEY = 'authorized';

    private Configuration $configuration;
    private Logger $logger;
    private Session $session;

    private ?bool $authorized = null;

    public function __construct(Configuration $configuration, Logger $logger, Session $session) {
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->session = $session;
    }

    public function canRead(): bool {
        return $this->configuration->public || $this->isPrivileged();
    }

    public function canUpdate(): bool {
        return $this->configuration->allowPublicUpdateAccess || $this->isPrivileged();
    }

    public function isPrivileged(): bool {
        if ($this->authorized === null) {
            $this->authorized = $this->checkSession() || $this->checkRequest();
        }

        return $this->authorized;
    }

    /**
     * Checks if there is a authorized session and matches it against the credentials in the config.
     */
    private function checkSession(): bool {
        $authorization = $this->session->getString(self::SESSION_KEY);

        if ($authorization === null) {
            $this->logger->debug('Session does not contain authorization string');
        } elseif ($authorization === $this->getExpectedAuthorizationSession()) {
            $this->logger->debug('Access granted based on a session');

            return true;
        }

        $this->logger->debug('Session does not contain valid auth (credentials changed)');

        return false;
    }

    /**
     * Checks if the request contains credentials and stores the authorization in a session if it does.
     */
    private function checkRequest(): bool {
        if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
            return false;
        }

        if ($this->verifyCredentials($_REQUEST['username'], $_REQUEST['password'])) {
            $this->logger->debug('Access granted based on request credentials');
            $this->saveAuthorization();

            return true;
        }

        $this->logger->debug('Request credentials not valid');

        return false;
    }

    /**
     * Generates a session value that will serve as a proof of authentication.
     *
     * We are storing the username & password hash to ensure clients
     * are logged out when credentials change.
     */
    private function getExpectedAuthorizationSession(): string {
        // We are using the password hash from the config for simplicity.
        // This will cause users to be signed out if they rehash the password but that should be rare.
        return $this->configuration->username . ':::' . $this->configuration->password;
    }

    /**
     * Checks credentials against config.
     */
    private function verifyCredentials(string $username, string $password): bool {
        return $username === $this->configuration->username && $this->verifyPassword($password);
    }

    /**
     * Checks password against config.
     */
    private function verifyPassword(string $password): bool {
        $hashedPassword = $this->configuration->password;

        // Passwords hashed with password_hash start with $, otherwise use the legacy path.
        return
            $hashedPassword !== '' && $hashedPassword[0] === '$'
            ? password_verify($password, $hashedPassword)
            : hash('sha512', $this->configuration->salt . $password) === $hashedPassword;
    }

    private function saveAuthorization(): void {
        $authorization = $this->getExpectedAuthorizationSession();
        $this->session->setString(self::SESSION_KEY, $authorization);
    }

    public function destroy(): void {
        $this->authorized = false;
        $this->session->setString(self::SESSION_KEY, null);
        $this->session->destroy();
        $this->logger->debug('Logged out and destroyed session');
    }
}
