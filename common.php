<?php

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

$autoloader = @include __DIR__ . '/vendor/autoload.php'; // we will show custom error
if ($autoloader === false) {
    echo 'The PHP dependencies are missing. Did you run `composer install` in the selfoss directory?';
    exit;
}

$f3 = $f3 = Base::instance();

$f3->set('DEBUG', 0);
$f3->set('version', '2.19-SNAPSHOT');
$f3->set('AUTOLOAD', false);
$f3->set('cache', __DIR__ . '/data/cache');
$f3->set('BASEDIR', __DIR__);
$f3->set('LOCALES', __DIR__ . '/public/lang/');
$f3->set('FTRSS_CUSTOM_DATA_DIR', __DIR__ . '/data/fulltextrss/custom');

// read defaults
$f3->config('defaults.ini');

// read config, if it exists
if (file_exists('config.ini')) {
    $f3->config('config.ini');
}

// overwrite config with ENV variables
$env_prefix = $f3->get('env_prefix');
foreach ($f3->get('ENV') as $key => $value) {
    if (strncasecmp($key, $env_prefix, strlen($env_prefix)) == 0) {
        $f3->set(strtolower(substr($key, strlen($env_prefix))), $value);
    }
}

// init logger
$log = new Logger('selfoss');
if ($f3->get('logger_level') !== 'NONE') {
    $logger_destination = $f3->get('logger_destination');

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
$f3->set('logger', $log);

// init error handling
$f3->set('ONERROR',
    function(Base $f3) {
        $exception = $f3->get('EXCEPTION');

        if ($exception) {
            \F3::get('logger')->error($exception->getMessage(), ['exception' => $exception]);
        } else {
            \F3::get('logger')->error($f3->get('ERROR.text'));
        }

        if (\F3::get('DEBUG') != 0) {
            echo $f3->get('lang_error') . ': ';
            echo $f3->get('ERROR.text') . "\n";
            echo $f3->get('ERROR.trace');
        } else {
            echo $f3->get('lang_error');
        }
    }
);

if (\F3::get('DEBUG') != 0) {
    ini_set('display_errors', 0);
}
