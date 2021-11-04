<?php

use Dice\Dice;
use helpers\Configuration;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require __DIR__ . '/constants.php';

$autoloader = @include BASEDIR . '/vendor/autoload.php'; // we will show custom error
if ($autoloader === false) {
    echo 'The PHP dependencies are missing. Did you run `composer install` in the selfoss directory?';
    exit;
}

$startup_error = error_get_last();

// F3 crashes when there were PHP startups error even though
// they might not affect the program (e.g. unable to load an extension).
// It also sets its own error_reporting value and uses the previous one
// as a signal to disable the initialization failure check.
error_reporting(0);

$f3 = Base::instance();

// Disable deprecation warnings.
// Dice uses ReflectionParameter::getClass(), which is deprecated in PHP 8
// but we have not set an error handler yet because it needs a Logger instantiated by Dice.
error_reporting(E_ALL & ~E_DEPRECATED);

$f3->set('AUTOLOAD', false);
$f3->set('BASEDIR', BASEDIR);
$f3->set('LOCALES', BASEDIR . '/assets/locale/');

$configuration = new Configuration(__DIR__ . '/../config.ini', $_ENV);

$f3->set('DEBUG', $configuration->debug);
$f3->set('cache', $configuration->cache);
$f3->set('language', $configuration->language);

$dice = new Dice();

// DI rules
$substitutions = [
    'substitutions' => [
        // Instantiate configuration container.
        Configuration::class => [
            'instance' => function() use ($configuration) {
                return $configuration;
            },
            'shared' => true,
        ],

        // Choose database implementation based on config
        daos\DatabaseInterface::class => ['instance' => 'daos\\' . $configuration->dbType . '\\Database'],
        daos\ItemsInterface::class => ['instance' => 'daos\\' . $configuration->dbType . '\\Items'],
        daos\SourcesInterface::class => ['instance' => 'daos\\' . $configuration->dbType . '\\Sources'],
        daos\TagsInterface::class => ['instance' => 'daos\\' . $configuration->dbType . '\\Tags'],

        Dice::class => ['instance' => function() use ($dice) {
            return $dice;
        }],
    ],
];

$shared = array_merge($substitutions, [
    'shared' => true,
]);

$dice->addRule(helpers\Authentication::class, $shared);

// Database bridges
$dice->addRule(daos\Items::class, $shared);
$dice->addRule(daos\Sources::class, $shared);
$dice->addRule(daos\Tags::class, $shared);

// Database implementation
$dice->addRule(daos\DatabaseInterface::class, $shared);
$dice->addRule(daos\ItemsInterface::class, $shared);
$dice->addRule(daos\SourcesInterface::class, $shared);
$dice->addRule(daos\TagsInterface::class, $shared);

if ($configuration->isChanged('dbSocket') && $configuration->isChanged('dbHost')) {
    echo 'You cannot set both `db_socket` and `db_host` options.' . PHP_EOL;
    exit;
}

// Database connection
if ($configuration->dbType === 'sqlite') {
    $db_file = $configuration->dbFile;

    // create empty database file if it does not exist
    if (!is_file($db_file)) {
        touch($db_file);
    }

    // https://www.php.net/manual/en/ref.pdo-sqlite.connection.php
    $dsn = 'sqlite:' . $db_file;
    $dbParams = [
        $dsn,
    ];
} elseif ($configuration->dbType === 'mysql') {
    $socket = $configuration->dbSocket;
    $host = $configuration->dbHost;
    $port = $configuration->dbPort;
    $database = $configuration->dbDatabase;

    // https://www.php.net/manual/en/ref.pdo-mysql.connection.php
    if ($socket !== null) {
        $dsn = "mysql:unix_socket=$socket; dbname=$database";
    } elseif ($port !== null) {
        $dsn = "mysql:host=$host; port=$port; dbname=$database";
    } else {
        $dsn = "mysql:host=$host; dbname=$database";
    }

    $dbParams = [
        $dsn,
        $configuration->dbUsername,
        $configuration->dbPassword,
        [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4;'],
    ];
} elseif ($configuration->dbType === 'pgsql') {
    $socket = $configuration->dbSocket;
    // PostgreSQL uses host key for socket.
    $host = $configuration->dbSocket !== null ? $configuration->dbSocket : $configuration->dbHost;
    $port = $configuration->dbPort;
    $database = $configuration->dbDatabase;

    // https://www.php.net/manual/en/ref.pdo-pgsql.connection.php
    if ($port !== null) {
        $dsn = "pgsql:host=$host; port=$port; dbname=$database";
    } else {
        $dsn = "pgsql:host=$host; dbname=$database";
    }

    $dbParams = [
        $dsn,
        $configuration->dbUsername,
        $configuration->dbPassword,
    ];
}

$sqlParams = array_merge($shared, [
    'constructParams' => $dbParams,
]);

// Define regexp function for SQLite
if ($configuration->dbType === 'sqlite') {
    $sqlParams = array_merge($sqlParams, [
        'call' => [
            [
                // DB\SQL uses PDO instance through composition
                // and forwards calls of non-existent methods to it.
                // But Dice can only call existing methods.
                // Let’s walk around these limitations by directly
                // calling the __call magic method.
                '__call',
                [
                    // https://www.sqlite.org/lang_expr.html#the_like_glob_regexp_and_match_operators
                    'sqliteCreateFunction',
                    [
                        'regexp',
                        function($pattern, $text) {
                            return preg_match('/' . addcslashes($pattern, '/') . '/', $text);
                        },
                        2,
                    ],
                ],
            ],
        ],
    ]);
}

$dice->addRule(DB\SQL::class, $sqlParams);

$dice->addRule('$iconStorageBackend', [
    'instanceOf' => helpers\Storage\FileStorage::class,
    'constructParams' => [
        $configuration->datadir . '/favicons',
    ],
]);

$dice->addRule(helpers\IconStore::class, array_merge($shared, [
    'constructParams' => [
        ['instance' => '$iconStorageBackend'],
    ],
]));

$dice->addRule('$thumbnailStorageBackend', [
    'instanceOf' => helpers\Storage\FileStorage::class,
    'constructParams' => [
        $configuration->datadir . '/thumbnails',
    ],
]);

$dice->addRule(helpers\ThumbnailStore::class, array_merge($shared, [
    'constructParams' => [
        ['instance' => '$thumbnailStorageBackend'],
    ],
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

$dice->addRule(helpers\FeedReader::class, [
    'constructParams' => [
        $configuration->cache,
    ],
]);

// init logger
$log = $dice->create(Logger::class);

if ($configuration->loggerLevel === 'NONE') {
    $handler = new NullHandler();
} else {
    $logger_destination = $configuration->loggerDestination;

    if (strpos($logger_destination, 'file:') === 0) {
        $handler = new StreamHandler(substr($logger_destination, 5), $configuration->loggerLevel);
    } elseif ($logger_destination === 'error_log') {
        $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $configuration->loggerLevel);
    } else {
        echo 'The `logger_destination` option needs to be either `error_log` or a file path prefixed by `file:`.';
        exit;
    }

    $formatter = new LineFormatter(null, null, true, true);
    $formatter->includeStacktraces(true);
    $handler->setFormatter($formatter);
}
$log->pushHandler($handler);

if (isset($startup_error)) {
    $log->warn('PHP likely encountered a startup error: ', [$startup_error]);
}

// init error handling
$f3->set('ONERROR',
    function(Base $f3) use ($configuration, $log, $handler) {
        $exception = $f3->get('EXCEPTION');

        try {
            if ($exception) {
                $log->error($exception->getMessage(), ['exception' => $exception]);
            } else {
                $log->error($f3->get('ERROR.text'));
            }

            if ($configuration->debug !== 0) {
                echo $f3->get('lang_error') . ': ';
                echo $f3->get('ERROR.text') . "\n";
                echo $f3->get('ERROR.trace');
            } else {
                if ($handler instanceof StreamHandler) {
                    echo $f3->get('lang_error_check_log_file', $handler->getUrl()) . PHP_EOL;
                } elseif ($handler instanceof ErrorLogHandler) {
                    echo $f3->get('lang_error_check_system_logs') . PHP_EOL;
                } else {
                    echo $f3->get('lang_error') . PHP_EOL;
                }
            }
        } catch (Exception $e) {
            echo $f3->get('lang_error_unwritable_logs') . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
        }
    }
);

if ($configuration->debug !== 0) {
    ini_set('display_errors', '0');
}
