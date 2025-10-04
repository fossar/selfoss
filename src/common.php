<?php

declare(strict_types=1);

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Selfoss\daos;
use Selfoss\helpers;
use Selfoss\helpers\Configuration;
use Selfoss\helpers\DatabaseConnection;
use Selfoss\helpers\WebClient;
use Slince\Di\Container;
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
    $configuration = new Configuration(__DIR__ . '/../config.ini', $_ENV);
} catch (Exception $e) {
    boot_error('Invalid configuration: ' . $e->getMessage() . PHP_EOL);
}

if ($configuration->debug !== 0) {
    // Enable strict mode to loudly fail on any error or warning.
    Debugger::$strictMode = true;
    // Switch to development mode so that traces are displayed.
    Debugger::enable(Debugger::Development);
}

$container = new Container();
$container->setDefaults(['shared' => false]);

// Instantiate configuration container.
$container
    ->register(Configuration::class, $configuration)
    ->setShared(true)
;

$container
    ->register(Bramus\Router\Router::class)
    ->setShared(true)
;
$container
    ->register(helpers\Authentication::class)
    ->setShared(true)
;

$container
    ->register(
        helpers\Authentication\AuthenticationService::class,
        [new Slince\Di\Reference(helpers\Authentication\AuthenticationFactory::class), 'create']
    )
    ->setShared(true)
;

$container
    ->register(helpers\Session::class)
    ->setShared(true)
;

// Database bridges
$container
    ->register(daos\Items::class)
    ->setShared(true)
;
$container
    ->register(daos\Sources::class)
    ->setShared(true)
;
$container
    ->register(daos\Tags::class)
    ->setShared(true)
;

// Choose database implementation based on config
$container
    ->register(daos\DatabaseInterface::class, 'Selfoss\daos\\' . $configuration->dbType . '\\Database')
    ->setShared(true)
;
$container
    ->register(daos\ItemsInterface::class, 'Selfoss\daos\\' . $configuration->dbType . '\\Items')
    ->setShared(true)
;
$container
    ->register(daos\SourcesInterface::class, 'Selfoss\daos\\' . $configuration->dbType . '\\Sources')
    ->setShared(true)
;
$container
    ->register(daos\TagsInterface::class, 'Selfoss\daos\\' . $configuration->dbType . '\\Tags')
    ->setShared(true)
;

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

$databaseConnection =
    $container
        ->register(DatabaseConnection::class)
        ->setArguments($dbParams)
        ->setShared(true)
;

// Define regexp function for SQLite
if ($configuration->dbType === 'sqlite') {
    $databaseConnection->addMethodCall(
        // https://www.sqlite.org/lang_expr.html#the_like_glob_regexp_match_and_extract_operators
        'sqliteCreateFunction',
        [
            'regexp',
            fn(string $pattern, string $text): bool => preg_match('/' . addcslashes($pattern, '/') . '/', $text) === 1,
            2,
        ]
    );
}

$container
    ->register('$iconStorageBackend', helpers\Storage\FileStorage::class)
    ->setArgument('directory', $configuration->datadir . '/favicons')
;

$container
    ->register(helpers\IconStore::class)
    ->setArgument('storage', new Slince\Di\Reference('$iconStorageBackend'))
    ->setShared(true)
;

$container
    ->register('$thumbnailStorageBackend', helpers\Storage\FileStorage::class)
    ->setArgument('directory', $configuration->datadir . '/thumbnails')
;

$container
    ->register(helpers\ThumbnailStore::class)
    ->setArgument('storage', new Slince\Di\Reference('$thumbnailStorageBackend'))
    ->setShared(true)
;

$container
    ->register(Logger::class)
    ->setArgument('name', 'selfoss')
    ->setShared(true)
;

$container
    ->register('$fileStorage', FilesystemAdapter::class)
    ->setArguments([
        'namespace' => 'selfoss',
        'lifetime' => 1800,
        'directory' => $configuration->cache,
    ])
    ->setShared(true)
;

$container
    ->register(CacheInterface::class, Psr16Cache::class)
    ->setArgument('pool', new Slince\Di\Reference('$fileStorage'))
    ->setShared(true)
;

$container
    ->register(ClientInterface::class, WebClient::class)
    ->setShared(true)
;

$container
    ->register(ContainerInterface::class, $container)
    ->setShared(true)
;

// init logger
$log = $container->get(Logger::class);

if ($configuration->loggerLevel === Configuration::LOGGER_LEVEL_NONE) {
    $handler = new NullHandler();
} else {
    $logger_destination = $configuration->loggerDestination;

    if (str_starts_with($logger_destination, 'file:')) {
        $handler = new StreamHandler(substr($logger_destination, 5), $configuration->loggerLevel);
    } elseif ($logger_destination === 'error_log') {
        $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $configuration->loggerLevel);
    } else {
        boot_error('The `logger_destination` option needs to be either `error_log` or a file path prefixed by `file:`.' . PHP_EOL);
    }

    $formatter = new LineFormatter(null, null, true, true);
    $formatter->includeStacktraces(true);
    $handler->setFormatter($formatter);
}
$log->pushHandler($handler);

$container
    ->register(Psr\Log\LoggerInterface::class, $log)
    ->setShared(true)
;

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
