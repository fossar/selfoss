<?php

declare(strict_types=1);

/*
 * SPDX-License-Identifier: GPL-3.0-or-later
 * SPDX-FileCopyrightText: 2025 Jan Tojnar <jtojnar@gmail.com>
 */

use Rector\Config\RectorConfig;
use Rector\Php73\Rector\String_\SensitiveHereNowDocRector;

return RectorConfig::configure()
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php80: true)
    ->withPreparedSets(
        typeDeclarations: true,
    )
    ->withSkip([
        // Case-insensitivity leads to false positives.
        SensitiveHereNowDocRector::class,
    ])
;
