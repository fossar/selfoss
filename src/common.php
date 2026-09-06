<?php

declare(strict_types=1);

use Kama\LiteWireDI\Container;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Selfoss\daos;
use Selfoss\helpers;
use Selfoss\helpers\Configuration;
use Selfoss\helpers\Configuration\LoggerLevel;
use Selfoss\helpers\DatabaseConnection;
use Selfoss\helpers\IconStore;
use Selfoss\helpers\ThumbnailStore;
use Selfoss\helpers\WebClient;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Tracy\Debugger;

require __DIR__ . '/constants.php';

function boot_error(string $message): never {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo $message;
    exit(1);
}

$autoloader = @include __DIR__ . '/../vendor/autoload.php'; // we will show custom error
if ($autoloader === false) {
    boot_error('The PHP dependencies are missing. Did you run `composer install` in the selfoss directory?' . PHP_EOL);
}

// Catch any errors and hopefully log them.
Debugger::$errorTemplate = __DIR__ . '/error.500.phtml';
Debugger::setSessionStorage(new Tracy\NativeSession());
Debugger::enable(Debugger::Production);

try {
    $configDir = $_ENV['SELFOSS_CONFIG_DIR'] ?? null;
    if ($configDir !== null && !is_dir($configDir)) {
        boot_error('The value of SELFOSS_CONFIG_DIR environment variable (' . $configDir . ') must be a directory');
    }
    $configDir ??= (__DIR__ . '/..');

    $configPath = $_ENV['SELFOSS_CONFIG_PATH'] ?? null;
    if ($configPath !== null && !is_file($configPath)) {
        boot_error('The value of SELFOSS_CONFIG_PATH (' . $configPath . ') must be a file');
    }
    $configPath ??= $configDir . '/config.ini';

    $configuration = new Configuration($configPath, $_ENV);
} catch (Throwable $e) {
    boot_error('Invalid configuration: ' . $e->getMessage() . PHP_EOL);
}

if ($configuration->debug !== 0) {
    // Enable strict mode to loudly fail on any error or warning.
    Debugger::$strictMode = true;
    // Switch to development mode so that traces are displayed.
    Debugger::enable(Debugger::Development);
}

$container = new Container();

// Instantiate configuration container.
$container->set(Configuration::class, $configuration);

$container->set(
    helpers\Authentication\AuthenticationService::class,
    static fn() => $container->make(helpers\Authentication\AuthenticationFactory::class)->create()
);

// Choose database implementation based on config
$container->set(daos\DatabaseInterface::class, 'Selfoss\daos\\' . $configuration->dbType . '\\Database');
$container->set(daos\ItemsInterface::class, 'Selfoss\daos\\' . $configuration->dbType . '\\Items');
$container->set(daos\SourcesInterface::class, 'Selfoss\daos\\' . $configuration->dbType . '\\Sources');
$container->set(daos\TagsInterface::class, 'Selfoss\daos\\' . $configuration->dbType . '\\Tags');

if ($configuration->isChanged('dbSocket') && $configuration->isChanged('dbHost')) {
    boot_error('You cannot set both `db_socket` and `db_host` options.' . PHP_EOL);
}

// Database connection
if ($configuration->dbType === 'sqlite') {
    if (!extension_loaded('pdo_sqlite')) {
        boot_error('Using SQLite database requires pdo_sqlite PHP extension. Please make sure you have it installed and enabled.');
    }
    $db_file = $configuration->dbFile;

    // create empty database file if it does not exist
    if (!is_file($db_file)) {
        touch($db_file);
    }

    // https://www.php.net/manual/en/ref.pdo-sqlite.connection.php
    $dsn = 'sqlite:' . $db_file;
    $dbParams = [
        'dsn' => $dsn,
    ];
} elseif ($configuration->dbType === 'mysql') {
    if (!extension_loaded('pdo_mysql')) {
        boot_error('Using MySQL database requires pdo_mysql PHP extension. Please make sure you have it installed and enabled.');
    }
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
        'dsn' => $dsn,
        'user' => $configuration->dbUsername,
        'password' => $configuration->dbPassword,
        'options' => [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4;'],
        'tableNamePrefix' => $configuration->dbPrefix,
    ];
} elseif ($configuration->dbType === 'pgsql') {
    if (!extension_loaded('pdo_pgsql')) {
        boot_error('Using PostgreSQL database requires pdo_pgsql PHP extension. Please make sure you have it installed and enabled.');
    }
    // PostgreSQL uses host key for socket.
    $host = $configuration->dbSocket ?? $configuration->dbHost;
    $port = $configuration->dbPort;
    $database = $configuration->dbDatabase;

    // https://www.php.net/manual/en/ref.pdo-pgsql.connection.php
    if ($port !== null) {
        $dsn = "pgsql:host=$host; port=$port; dbname=$database";
    } else {
        $dsn = "pgsql:host=$host; dbname=$database";
    }

    $dbParams = [
        'dsn' => $dsn,
        'user' => $configuration->dbUsername,
        'password' => $configuration->dbPassword,
    ];
} else {
    boot_error('Unsupported value for db_type option: ' . $configuration->dbType . PHP_EOL);
}

$container->set(
    DatabaseConnection::class,
    static function(Container $container) use ($configuration, $dbParams) {
        $databaseConnection = $container->make(DatabaseConnection::class, $dbParams);
        // Define regexp function for SQLite
        if ($configuration->dbType === 'sqlite') {
            // https://www.sqlite.org/lang_expr.html#the_like_glob_regexp_match_and_extract_operators
            $databaseConnection->sqliteCreateFunction(
                'regexp',
                fn(string $pattern, string $text): bool => preg_match('/' . addcslashes($pattern, '/') . '/', $text) === 1,
                2,
            );
        }

        return $databaseConnection;
    }
);

$container->set(
    IconStore::class,
    static fn(Container $container) => $container->make(
        IconStore::class,
        ['storage' => $container->make(
            helpers\Storage\FileStorage::class,
            ['directory' => $configuration->datadir . '/favicons'],
        )],
    ),
);

$container->set(
    ThumbnailStore::class,
    static fn(Container $container) => $container->make(
        ThumbnailStore::class,
        ['storage' => $container->make(
            helpers\Storage\FileStorage::class,
            ['directory' => $configuration->datadir . '/thumbnails'],
        )],
    ),
);

$container->set(Logger::class, ['name' => 'selfoss']);

$container->set(
    CacheInterface::class,
    static fn(Container $container) => $container->make(
        Psr16Cache::class,
        ['pool' => $container->make(
            FilesystemAdapter::class,
            [
                'namespace' => 'selfoss',
                'lifetime' => 1800,
                'directory' => $configuration->cache,
            ],
        )],
    ),
);

$container->set(ClientInterface::class, WebClient::class);

// init logger
$log = $container->get(Logger::class);

if ($configuration->loggerLevel === LoggerLevel::None) {
    $handler = new NullHandler();
} else {
    $logger_destination = $configuration->loggerDestination;

    if (str_starts_with($logger_destination, 'file:')) {
        $handler = new StreamHandler(substr($logger_destination, 5), $configuration->loggerLevel->value);
    } elseif ($logger_destination === 'error_log') {
        $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $configuration->loggerLevel->value);
    } else {
        boot_error('The `logger_destination` option needs to be either `error_log` or a file path prefixed by `file:`.' . PHP_EOL);
    }

    $formatter = new LineFormatter(null, null, true, true);
    $formatter->includeStacktraces(true);
    $handler->setFormatter($formatter);
}
$log->pushHandler($handler);

$container->set(Psr\Log\LoggerInterface::class, $log);

// Try to log errors encountered by error handler.
Debugger::setLogger($container->get(Tracy\Bridges\Psr\PsrToTracyLoggerAdapter::class));
if ($configuration->debug !== 0) {
    // Tracy will not use logger in development mode, let’s do it ourselves.
    Debugger::$onFatalError[] = function(Throwable $error) use ($log): void {
        $log->error('Unhandled error occurred.', ['exception' => $error]);
    };

    if (!Tracy\Helpers::isCli()) {
        // AJAX support requires session to be started before dispatch.
        $session = $container->get(helpers\Session::class);
        $session->start();
    }
}
Debugger::dispatch();
