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

use helpers\Authentication\AuthenticationService;

/**
 * Helper class for user authentication.
 *
 * selfoss currently has three kinds of controlled resources that user can be authorized to access:
 *
 *  - **Read**: Read-only access available to unauthenticated users if *public mode* is enabled.
 *  - **Update**: Allows triggering source updates when *public update mode* is enabled.
 *  - **Privileged**: Any other operation (admin) user, full access without any limitations.
 */
class Authentication {
    private AuthenticationService $authenticationService;

    public function __construct(AuthenticationService $authenticationService) {
        $this->authenticationService = $authenticationService;
    }

    /**
     * login enabled
     */
    public function enabled(): bool {
        return !$this->authenticationService instanceof Authentication\Services\Trust;
    }

    /**
     * showPrivateTags
     */
    public function showPrivateTags(): bool {
        return $this->authenticationService->isPrivileged();
    }

    /**
     * If user is not authorized to read, force them to authenticate.
     */
    public function ensureCanRead(): void {
        if (!$this->authenticationService->canRead()) {
            $this->forbidden();
        }
    }

    /**
     * If user is not authorized to privileged operations, force them to authenticate.
     */
    public function ensureIsPrivileged(): void {
        if (!$this->authenticationService->isPrivileged()) {
            $this->forbidden();
        }
    }

    /**
     * send 403 if not logged in
     *
     * @return never
     */
    private function forbidden(): void {
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
     */
    public function allowedToUpdate(): bool {
        return $this->authenticationService->canUpdate();
    }
}
