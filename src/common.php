<?php

declare(strict_types=1);

use helpers\Configuration;
use helpers\DatabaseConnection;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Slince\Di\Container;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

require __DIR__ . '/constants.php';

/**
 * @return never
 */
function boot_error(string $message) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo $message;
    exit(1);
}

$autoloader = @include __DIR__ . '/../vendor/autoload.php'; // we will show custom error
if ($autoloader === false) {
    boot_error('The PHP dependencies are missing. Did you run `composer install` in the selfoss directory?' . PHP_EOL);
}

$startup_error = error_get_last();

// F3 crashes when there were PHP startups error even though
// they might not affect the program (e.g. unable to load an extension).
// It also sets its own error_reporting value and uses the previous one
// as a signal to disable the initialization failure check.
error_reporting(0);

$f3 = Base::instance();

error_reporting(E_ALL);

$f3->set('AUTOLOAD', false);
$f3->set('BASEDIR', BASEDIR);

$configuration = new Configuration(__DIR__ . '/../config.ini', $_ENV);

$f3->set('DEBUG', $configuration->debug);
$f3->set('cache', $configuration->cache);

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
    ->register(daos\DatabaseInterface::class, 'daos\\' . $configuration->dbType . '\\Database')
    ->setShared(true)
;
$container
    ->register(daos\ItemsInterface::class, 'daos\\' . $configuration->dbType . '\\Items')
    ->setShared(true)
;
$container
    ->register(daos\SourcesInterface::class, 'daos\\' . $configuration->dbType . '\\Sources')
    ->setShared(true)
;
$container
    ->register(daos\TagsInterface::class, 'daos\\' . $configuration->dbType . '\\Tags')
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
    throw new Exception('Unsupported value for db_type option: ' . $configuration->dbType);
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
            function(string $pattern, string $text): bool {
                return preg_match('/' . addcslashes($pattern, '/') . '/', $text) === 1;
            },
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

if (isset($startup_error)) {
    $log->warning('PHP likely encountered a startup error: ', [$startup_error]);
}

// init error handling
$f3->set(
    'ONERROR',
    function(Base $f3) use ($configuration, $log, $handler): void {
        $exception = $f3->get('EXCEPTION');

        try {
            if ($exception) {
                $log->error($exception->getMessage(), ['exception' => $exception]);
            } else {
                $log->error($f3->get('ERROR.text'));
            }

            if ($configuration->debug !== 0) {
                echo 'An error occurred: ';
                echo $f3->get('ERROR.text') . "\n";
                echo $f3->get('ERROR.trace');
            } else {
                if ($handler instanceof StreamHandler) {
                    echo 'An error occured, please check the log file “' . $handler->getUrl() . '”.' . PHP_EOL;
                } elseif ($handler instanceof ErrorLogHandler) {
                    echo 'An error occured, please check your system logs.' . PHP_EOL;
                } else {
                    echo 'An error occurred' . PHP_EOL;
                }
            }
        } catch (Exception $e) {
            echo 'Unable to write logs.' . PHP_EOL;
            echo $e->getMessage() . PHP_EOL;
        }
    }
);

if ($configuration->debug !== 0) {
    ini_set('display_errors', '0');
}
