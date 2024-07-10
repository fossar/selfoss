<?php

// SPDX-FileCopyrightText: 2024 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Authentication\Services;

use helpers\Authentication\AuthenticationService;

/**
 * Trivial authentication service that allows any access.
 *
 * To be used for CLI or when authentication is disabled.
 */
final class Trust implements AuthenticationService {
    public function canRead(): bool {
        return true;
    }

    public function canUpdate(): bool {
        return true;
    }

    public function isPrivileged(): bool {
        return true;
    }

    public function destroy(): void {
        // Nothing to rescind.
    }
}
