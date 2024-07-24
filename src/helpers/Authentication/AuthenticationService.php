<?php

// SPDX-FileCopyrightText: 2024 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace helpers\Authentication;

/**
 * Must be implemented by any authentication service.
 *
 * selfoss currently has three kinds of controlled resources that user can be authorized to access:
 *
 *  - **Read**: Read-only access available to unauthenticated users if *public mode* is enabled.
 *  - **Update**: Allows triggering source updates when *public update mode* is enabled.
 *  - **Privileged**: Any other operation (admin) user, full access without any limitations.
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
     * If user is not authorized to read, force them to authenticate.
     *
     * This is enforcing variant of `canRead()` method.
     *
     * The method should terminate the application, either by
     * throwing an exception or sending a HTTP response and exiting.
     */
    public function ensureCanRead(): void;

    /**
     * If user is not authorized, force them to authenticate.
     *
     * This is enforcing variant of `isPrivileged()` method.
     *
     * The method should terminate the application, either by
     * throwing an exception or sending a HTTP response and exiting.
     */
    public function ensureIsPrivileged(): void;

    /**
     * Give up authorization.
     */
    public function destroy(): void;
}
