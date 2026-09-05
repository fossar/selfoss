<?php

// SPDX-FileCopyrightText: 2014 Jean Baptiste Favre <github@jbfavre.org>
// SPDX-FileCopyrightText: 2026 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Selfoss\helpers;

use Graby\Graby;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Monolog\Logger;

final readonly class GrabyFactory {
    /** Tag for logger */
    private const LOGGER_TAG = 'selfoss.graby';

    public function __construct(
        private Configuration $configuration,
        private Logger $logger,
        private WebClient $webClient
    ) {
    }

    public function create(): Graby {
        $graby = new Graby([
            'extractor' => [
                'config_builder' => [
                    'site_config' => [$this->configuration->ftrssCustomDataDir],
                ],
            ],
        ], new GuzzleAdapter($this->webClient->getHttpClient()));

        $logger = $this->logger->withName(self::LOGGER_TAG);
        $graby->setLogger($logger);

        return $graby;
    }
}
