<?php

// SPDX-FileCopyrightText: 2016–2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Selfoss\helpers;

interface UpdateVisitor {
    public function started(int $count): void;

    public function sourceUpdated(): void;

    public function finished(): void;
}
