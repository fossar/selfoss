<?php

/**
 * @package Demos
 */
define('DEMO_PATH', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR);

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

require_once DEMO_PATH . 'helpers/Request.php';
require_once DEMO_PATH . 'helpers/Demo.php';
require_once DEMO_PATH . 'helpers/Field.php';
require_once DEMO_PATH . 'helpers/CheckboxField.php';
require_once DEMO_PATH . 'helpers/FileSelectField.php';
require_once DEMO_PATH . 'helpers/CoordinateField.php';
require_once DEMO_PATH . 'helpers/IntField.php';
require_once DEMO_PATH . 'helpers/FloatField.php';
require_once DEMO_PATH . 'helpers/AngleField.php';
require_once DEMO_PATH . 'helpers/SelectField.php';
require_once DEMO_PATH . 'helpers/FormatSelectField.php';
require_once DEMO_PATH . 'helpers/ColorField.php';

function __autoload($className) {
    $className = ltrim($className, '\\');
    $fileName = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    require DEMO_PATH.'../lib/'.$fileName;
}

