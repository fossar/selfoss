<?php

// SPDX-FileCopyrightText: 2024 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Authentication;

use helpers\Configuration;
use Psr\Container\ContainerInterface;

/**
 * Factory that creates `AuthenticationService` based on the configuration.
 */
final class AuthenticationFactory {
    private Configuration $configuration;
    private ContainerInterface $container;

    public function __construct(Configuration $configuration, ContainerInterface $container) {
        $this->configuration = $configuration;
        $this->container = $container;
    }

    public function create(): AuthenticationService {
        if (!$this->useCredentials() || $this->isCli() || $this->isLocalIp()) {
            return $this->container->get(Services\Trust::class);
        }

        return $this->container->get(Services\RequestOrSession::class);
    }

    private function isCli(): bool {
        return PHP_SAPI === 'cli';
    }

    private function isLocalIp(): bool {
        // We cannot trust these IP addresses but we know they are likely not local.
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) || isset($_SERVER['HTTP_FORWARDED'])) {
            return false;
        }

        return $_SERVER['REMOTE_ADDR'] === '::1' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1';
    }

    private function useCredentials(): bool {
        return strlen($this->configuration->username) > 0 && strlen($this->configuration->password) > 0;
    }
}
