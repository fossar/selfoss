<?php

use helpers\Configuration;

require __DIR__ . '/../vendor/autoload.php';

$reflection = new ReflectionClass(Configuration::class);

$defaults = '; see https://selfoss.aditu.de/docs/administration/options/' . PHP_EOL;
$defaults .= '; for more information about the configuration parameters' . PHP_EOL;
$defaults .= '[globals]' . PHP_EOL;

foreach ($reflection->getProperties() as $property) {
    if (strpos($property->getDocComment(), '@internal') !== false) {
        continue;
    }

    $propertyName = $property->getName();
    $configKey = strtolower(preg_replace('([[:upper:]]+)', '_$0', $propertyName));
    $defaultValue = $property->getDeclaringClass()->getDefaultProperties()[$propertyName];

    if ($defaultValue === true) {
        $defaultValue = '1';
    } elseif ($defaultValue === false) {
        $defaultValue = '0';
    }

    $defaults .= $configKey . '=' . $defaultValue . PHP_EOL;
}

file_put_contents(__DIR__ . '/../defaults.ini', $defaults);
