<?php

/**
 * SPDX-License-Identifier: BSD-3-Clause
 * Copyright 2016 Jan Škrášek
 *
 * @see https://github.com/nextras/tracy-monolog-adapter
 */

namespace helpers;

use Exception;
use Monolog;
use Throwable;
use Tracy\Helpers;
use Tracy\ILogger;

class TracyLogger implements ILogger {
    /** @const Tracy priority to Monolog priority mapping */
    const PRIORITY_MAP = [
        self::DEBUG => Monolog\Logger::DEBUG,
        self::INFO => Monolog\Logger::INFO,
        self::WARNING => Monolog\Logger::WARNING,
        self::ERROR => Monolog\Logger::ERROR,
        self::EXCEPTION => Monolog\Logger::CRITICAL,
        self::CRITICAL => Monolog\Logger::CRITICAL,
    ];

    /** @var Monolog\Logger */
    protected $monolog;

    public function __construct(Monolog\Logger $monolog) {
        $this->monolog = $monolog;
    }

    public function log($message, $priority = self::INFO) {
        $context = [
            'at' => Helpers::getSource(),
        ];

        if ($message instanceof Throwable || $message instanceof Exception) {
            $context['exception'] = $message;
            $message = '';
        }

        $this->monolog->addRecord(
            self::PRIORITY_MAP[$priority] ?: Monolog\Logger::ERROR,
            $message,
            $context
        );
    }
}
