<?php

use helpers\Configuration;

require __DIR__ . '/../vendor/autoload.php';

// Fail loudly on warnings.
set_error_handler(function(int $severity, string $message, string $file, int $line): bool {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

$reflection = new ReflectionClass(Configuration::class);

$example = '; see https://selfoss.aditu.de/docs/administration/options/' . PHP_EOL;
$example .= '; for more information about the configuration parameters' . PHP_EOL;

foreach ($reflection->getProperties() as $property) {
    if (!$property->isPublic() || strpos($property->getDocComment(), '@internal') !== false) {
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

    $example .= $configKey . '=' . $defaultValue . PHP_EOL;
}

file_put_contents(__DIR__ . '/../config-example.ini', $example);
