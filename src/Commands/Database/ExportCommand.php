<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Commands\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command {
    protected static $defaultName = 'db:export';
    protected static $defaultDescription = 'Exports the database contents into a JSON file.';

    /** @var \daos\DatabaseInterface database object to create the tables */
    private $database;

    /** @var \daos\Items */
    private $items;

    /** @var \daos\Sources */
    private $sources;

    /** @var \daos\Tags */
    private $tags;

    public function __construct(
        \daos\DatabaseInterface $database,
        \daos\Items $items,
        \daos\Sources $sources,
        \daos\Tags $tags
    ) {
        $this->database = $database;
        $this->items = $items;
        $this->sources = $sources;
        $this->tags = $tags;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure() {
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to file to export the database to.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $data = [
            'schemaVersion' => $this->database->getSchemaVersion(),
            'tags' => $this->tags->getRaw(),
            'sources' => $this->sources->getRaw(),
            'items' => $this->items->getRaw(),
        ];

        file_put_contents($input->getArgument('path'), json_encode($data));

        return self::SUCCESS;
    }
}
