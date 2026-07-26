<?php

// SPDX-FileCopyrightText: 2024 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Selfoss\helpers\Configuration;

enum LoggerLevel: string {
    case Emergency = 'EMERGENCY';
    case Alert = 'ALERT';
    case Critical = 'CRITICAL';
    case Error = 'ERROR';
    case Warning = 'WARNING';
    case Notice = 'NOTICE';
    case Info = 'INFO';
    case Debug = 'DEBUG';
    case None = 'NONE';
}
