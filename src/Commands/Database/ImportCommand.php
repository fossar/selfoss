<?php

// SPDX-FileCopyrightText: 2023 Jan Tojnar <jtojnar@gmail.com>
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Commands\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command {
    protected static $defaultName = 'db:import';
    protected static $defaultDescription = 'Imports the database contents from a JSON file.';

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
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to file to import the database from.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $data = file_get_contents($input->getArgument('path'));
        assert($data !== false);
        $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        $currentDbSchemaVersion = $this->database->getSchemaVersion();

        if ($data['schemaVersion'] !== $currentDbSchemaVersion) {
            $output->writeln('<comment>The database schema version of the current selfoss installation does not match the installation the data was exported from, which may cause issues. For best compatibility, upgrade ' . ($data['schemaVersion'] < $currentDbSchemaVersion ? 'the source selfoss' : 'this selfoss') . ' installation before importing.</comment>');
        }

        $this->tags->insertRaw($data['tags']);
        $this->sources->insertRaw($data['sources']);
        $this->items->insertRaw($data['items']);

        return self::SUCCESS;
    }
}
