<?php

// SPDX-FileCopyrightText: 2024 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Authentication;

/**
 * Must be implemented by any authentication service.
 */
interface AuthenticationService {
    /**
     * Checks whether user is authorized to read (logged in/public mode).
     */
    public function canRead(): bool;

    /**
     * Checks whether user is authorized to update sources (logged in/public update mode).
     */
    public function canUpdate(): bool;

    /**
     * Checks whether user is authorized to perform a privileged action
     * or access privileged information.
     */
    public function isPrivileged(): bool;

    /**
     * Give up authorization.
     */
    public function destroy(): void;
}
