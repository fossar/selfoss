<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Commands;

use helpers\ContentLoader;
use helpers\UpdateVisitor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command {
    /** @var string */
    public static $defaultName = 'update';
    /** @var string */
    public static $defaultDescription = 'Fetches latest items for all sources';

    private ContentLoader $contentLoader;

    public function __construct(
        ContentLoader $contentLoader
    ) {
        $this->contentLoader = $contentLoader;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $updateVisitor = new class() implements UpdateVisitor {
            public function started(int $count): void {
            }

            public function sourceUpdated(): void {
            }

            public function finished(): void {
            }
        };

        $this->contentLoader->update($updateVisitor);

        return self::SUCCESS;
    }
}
