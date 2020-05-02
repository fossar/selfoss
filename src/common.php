<?php

use Dice\Dice;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

define('BASEDIR', __DIR__ . '/..');

$autoloader = @include BASEDIR . '/vendor/autoload.php'; // we will show custom error
if ($autoloader === false) {
    echo 'The PHP dependencies are missing. Did you run `composer install` in the selfoss directory?';
    exit;
}

$f3 = Base::instance();

$f3->set('DEBUG', 0);
$f3->set('version', '2.19-SNAPSHOT');

// independent of selfoss version
// needs to be bumped each time public API is changed (follows semver)
// keep in sync with docs/api-description.json
$f3->set('apiversion', '2.21.0');

$f3->set('AUTOLOAD', false);
$f3->set('BASEDIR', BASEDIR);
$f3->set('LOCALES', BASEDIR . '/assets/locale/');

// internal but overridable values
$f3->set('datadir', BASEDIR . '/data');
$f3->set('cache', '%datadir%/cache');
$f3->set('ftrss_custom_data_dir', '%datadir%/fulltextrss');

// read defaults
$f3->config('defaults.ini');

// read config, if it exists
if (file_exists('config.ini')) {
    $f3->config('config.ini');
}

// overwrite config with ENV variables
$env_prefix = $f3->get('env_prefix');
foreach ($f3->get('ENV') as $key => $value) {
    if (strncasecmp($key, $env_prefix, strlen($env_prefix)) === 0) {
        $f3->set(strtolower(substr($key, strlen($env_prefix))), $value);
    }
}

// interpolate variables in the config values
$interpolatedKeys = [
    'db_file',
    'logger_destination',
    'cache',
    'ftrss_custom_data_dir',
];
$datadir = $f3->get('datadir');
foreach ($interpolatedKeys as $key) {
    $value = $f3->get($key);
    $f3->set($key, str_replace('%datadir%', $datadir, $value));
}

$dice = new Dice();

// DI rules
// Choose database implementation based on config
$substitutions = [
    'substitutions' => [
        daos\DatabaseInterface::class => ['instance' => 'daos\\' . $f3->get('db_type') . '\\Database'],
        daos\ItemsInterface::class => ['instance' => 'daos\\' . $f3->get('db_type') . '\\Items'],
        daos\SourcesInterface::class => ['instance' => 'daos\\' . $f3->get('db_type') . '\\Sources'],
        daos\TagsInterface::class => ['instance' => 'daos\\' . $f3->get('db_type') . '\\Tags'],
    ]
];

$shared = array_merge($substitutions, [
    'shared' => true,
]);

$dice->addRule(helpers\Authentication::class, $shared);

// Database bridges
$dice->addRule(daos\Database::class, $shared);
$dice->addRule(daos\Items::class, $shared);
$dice->addRule(daos\Sources::class, $shared);
$dice->addRule(daos\Tags::class, $shared);

// Database implementation
$dice->addRule(daos\DatabaseInterface::class, $shared);
$dice->addRule(daos\ItemsInterface::class, $shared);
$dice->addRule(daos\SourcesInterface::class, $shared);
$dice->addRule(daos\TagsInterface::class, $shared);

// Database connection
if ($f3->get('db_type') === 'sqlite') {
    $db_file = $f3->get('db_file');

    // create empty database file if it does not exist
    if (!is_file($db_file)) {
        touch($db_file);
    }

    $dsn = 'sqlite:' . $db_file;
    $dbParams = [
        $dsn
    ];
} elseif ($f3->get('db_type') === 'mysql') {
    $host = $f3->get('db_host');
    $port = $f3->get('db_port');
    $database = $f3->get('db_database');

    if ($port) {
        $dsn = "mysql:host=$host; port=$port; dbname=$database";
    } else {
        $dsn = "mysql:host=$host; dbname=$database";
    }

    $dbParams = [
        $dsn,
        $f3->get('db_username'),
        $f3->get('db_password'),
        [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4;']
    ];
} elseif ($f3->get('db_type') === 'pgsql') {
    $host = $f3->get('db_host');
    $port = $f3->get('db_port');
    $database = $f3->get('db_database');

    if ($port) {
        $dsn = "pgsql:host=$host; port=$port; dbname=$database";
    } else {
        $dsn = "pgsql:host=$host; dbname=$database";
    }

    $dbParams = [
        $dsn,
        $f3->get('db_username'),
        $f3->get('db_password')
    ];
}

$dice->addRule(DB\SQL::class, array_merge($shared, [
    'constructParams' => $dbParams
]));

// Fallback rule
$dice->addRule('*', $substitutions);

$f3->set('CONTAINER', function($class) use ($dice) {
    return $dice->create($class);
});

$dice->addRule(Logger::class, [
    'shared' => true,
    'constructParams' => ['selfoss'],
]);

$dice->addRule(helpers\FeedHelper::class, [
    'constructParams' => [
        \F3::get('cache'),
    ],
]);

// init logger
$log = $dice->create(Logger::class);
if ($f3->get('logger_level') === 'NONE') {
    $log->pushHandler(new NullHandler());
} else {
    $logger_destination = in_array(PHP_SAPI, ['cli', 'cli-server'], true) ? 'error_log' : $f3->get('logger_destination');

    if (strpos($logger_destination, 'file:') === 0) {
        $handler = new StreamHandler(substr($logger_destination, 5), $f3->get('logger_level'));
    } elseif ($logger_destination === 'error_log') {
        $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $f3->get('logger_level'));
    } else {
        echo 'The `logger_destination` option needs to be either `error_log` or a file path prefixed by `file:`.';
        exit;
    }

    $formatter = new LineFormatter(null, null, true, true);
    $formatter->includeStacktraces(true);
    $handler->setFormatter($formatter);
    $log->pushHandler($handler);
}

// init error handling
$f3->set('ONERROR',
    function(Base $f3) use ($log) {
        $exception = $f3->get('EXCEPTION');

        if ($exception) {
            $log->error($exception->getMessage(), ['exception' => $exception]);
        } else {
            $log->error($f3->get('ERROR.text'));
        }

        if ($f3->get('DEBUG') != 0) {
            echo $f3->get('lang_error') . ': ';
            echo $f3->get('ERROR.text') . "\n";
            echo $f3->get('ERROR.trace');
        } else {
            echo $f3->get('lang_error');
        }
    }
);

if ($f3->get('DEBUG') != 0) {
    ini_set('display_errors', '0');
}
