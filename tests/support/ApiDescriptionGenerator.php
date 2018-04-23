<?php

use Codeception\Configuration;
use Codeception\Event\SuiteEvent;
use Codeception\Events;

class ApiDescriptionGenerator extends \Codeception\Extension {
    public static $events = [
        Events::SUITE_INIT => 'createApiDescription',
    ];

    public function createApiDescription(SuiteEvent $e) {
        $modules = $e->getSuite()->getModules();
        $sourceFile = Configuration::projectDir() . 'docs' . DIRECTORY_SEPARATOR . 'api-description.json';
        $targetFile = Configuration::supportDir() . '_generated' . DIRECTORY_SEPARATOR . 'api-description.json';
        $script = Configuration::supportDir() . 'openapi2jsonschema.js';

        codecept_debug('Generating API description with JSON Schemas...');
        if (!file_exists(Configuration::supportDir() . '_generated')) {
            @mkdir(Configuration::supportDir() . '_generated');
        }

        $output = ['Generating API description failed:'];
        $status = 0;
        $out = exec('node ' . escapeshellarg($script) . ' ' . escapeshellarg($sourceFile) . ' ' . escapeshellarg($targetFile) . ' 2>&1', $output, $status);
        if ($status !== 0) {
            throw new \Exception(implode("\n", $output));
        }
    }
}
