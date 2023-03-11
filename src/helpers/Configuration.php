<?php

declare(strict_types=1);

namespace helpers;

use Exception;
use ReflectionClass;

/**
 * Configuration container.
 *
 * @see https://selfoss.aditu.de/docs/administration/options/ for more information about the configuration parameters.
 */
class Configuration {
    /** @var string[] List of config values that should have variables interpolated. */
    public const INTERPOLATED_PROPERTIES = [
        'dbFile',
        'loggerDestination',
        'cache',
        'ftrssCustomDataDir',
    ];

    public const LOGGER_LEVEL_EMERGENCY = 'EMERGENCY';
    public const LOGGER_LEVEL_ALERT = 'ALERT';
    public const LOGGER_LEVEL_CRITICAL = 'CRITICAL';
    public const LOGGER_LEVEL_ERROR = 'ERROR';
    public const LOGGER_LEVEL_WARNING = 'WARNING';
    public const LOGGER_LEVEL_NOTICE = 'NOTICE';
    public const LOGGER_LEVEL_INFO = 'INFO';
    public const LOGGER_LEVEL_DEBUG = 'DEBUG';
    public const LOGGER_LEVEL_NONE = 'NONE';

    private const ALLOWED_LOGGER_LEVELS = [
        self::LOGGER_LEVEL_EMERGENCY,
        self::LOGGER_LEVEL_ALERT,
        self::LOGGER_LEVEL_CRITICAL,
        self::LOGGER_LEVEL_ERROR,
        self::LOGGER_LEVEL_WARNING,
        self::LOGGER_LEVEL_NOTICE,
        self::LOGGER_LEVEL_INFO,
        self::LOGGER_LEVEL_DEBUG,
        self::LOGGER_LEVEL_NONE,
    ];

    /** @var array<string, bool> Keeps track of options that have been changed. */
    private $modifiedOptions = [];

    // Internal but overridable values.

    /** @var int debugging level @internal */
    public $debug = 0;

    /** @var string @internal */
    public $datadir = __DIR__ . '/../../data';

    /** @var string @internal */
    public $cache = '%datadir%/cache';

    /** @var string @internal */
    public $ftrssCustomDataDir = '%datadir%/fulltextrss';

    // Rest of the values.

    /** @var string */
    public $dbType = 'sqlite';

    /** @var string */
    public $dbFile = '%datadir%/sqlite/selfoss.db';

    /** @var string */
    public $dbHost = 'localhost';

    /** @var string */
    public $dbDatabase = 'selfoss';

    /** @var string */
    public $dbUsername = 'root';

    /** @var string */
    public $dbPassword = '';

    /** @var ?int */
    public $dbPort = null;

    /** @var ?string */
    public $dbSocket = null;

    /** @var string */
    public $dbPrefix = '';

    /** @var string */
    public $loggerDestination = 'file:%datadir%/logs/default.log';

    /** @var self::LOGGER_LEVEL_* */
    public $loggerLevel = 'ERROR';

    /** @var int */
    public $itemsPerpage = 50;

    /** @var int */
    public $itemsLifetime = 30;

    /** @var string */
    public $baseUrl = '';

    /** @var string */
    public $username = '';

    /** @var string */
    public $password = '';

    /** @var string */
    public $salt = 'lkjl1289';

    /** @var bool */
    public $public = false;

    /** @var string */
    public $htmlTitle = 'selfoss';

    /** @var string */
    public $rssTitle = 'selfoss feed';

    /** @var int */
    public $rssMaxItems = 300;

    /** @var bool */
    public $rssMarkAsRead = false;

    /** @var string */
    public $homepage = 'newest';

    /** @var ?string */
    public $language = null;

    /** @var bool */
    public $autoMarkAsRead = false;

    /** @var bool */
    public $autoCollapse = false;

    /** @var bool */
    public $autoStreamMore = true;

    /** @var bool */
    public $openInBackgroundTab = false;

    /** @var string */
    public $share = 'atfpde';

    /** @var string */
    public $wallabag = '';

    /** @var string */
    public $wallabagVersion = '2';

    /** @var ?string */
    public $wordpress = null;

    /** @var ?string */
    public $mastodon = null;

    /** @var bool */
    public $allowPublicUpdateAccess = false;

    /** @var string */
    public $unreadOrder = 'desc';

    /** @var bool */
    public $loadImagesOnMobile = false;

    /** @var bool */
    public $autoHideReadOnMobile = false;

    /** @var string */
    public $envPrefix = 'selfoss_';

    /** @var string */
    public $camoDomain = '';

    /** @var string */
    public $camoKey = '';

    /** @var bool */
    public $scrollToArticleHeader = true;

    /** @var bool */
    public $showThumbnails = true;

    /** @var int */
    public $readingSpeedWpm = 0;

    /**
     * @param array<string, string> $environment
     */
    public function __construct(?string $configPath = null, array $environment = []) {
        // read config.ini, if it exists
        if ($configPath !== null && file_exists($configPath)) {
            $config = parse_ini_file($configPath, false, INI_SCANNER_RAW);
            if ($config === false) {
                throw new Exception('Error loading config.ini');
            }
        } else {
            $config = [];
        }

        // overwrite config with ENV variables
        if (isset($config['env_prefix'])) {
            $this->envPrefix = $config['env_prefix'];
        }

        $reflection = new ReflectionClass(self::class);
        foreach ($reflection->getProperties() as $property) {
            $underscoreSeparatedName = preg_replace('([[:upper:]]+)', '_$0', $property->getName());
            assert($underscoreSeparatedName !== null, 'Regex must be valid');
            $configKey = strtolower($underscoreSeparatedName);

            if (isset($environment[strtoupper($this->envPrefix . $configKey)])) {
                // Prefer the value from environment variable if present.
                $value = $environment[strtoupper($this->envPrefix . $configKey)];
            } elseif (isset($environment[$this->envPrefix . $configKey])) {
                // Also try lowercase spelling.
                $value = $environment[$this->envPrefix . $configKey];
            } elseif (isset($config[$configKey])) {
                // Finally, try the value from config.ini.
                $value = $config[$configKey];
            } else {
                // Otherwise, just leave the default value.
                continue;
            }

            $value = trim($value);

            preg_match('(@var (?P<nullable>\??)(?P<type>[^\s]+))', $property->getDocComment() ?: '', $matches);
            if ($matches['nullable'] === '?' && $value === '') {
                // Keep the default value for empty nullables.
                continue;
            }

            $propertyName = $property->getName();
            $propertyType = $matches['type'];
            if ($propertyType === 'bool') {
                $value = (bool) $value;
            } elseif ($propertyType === 'int') {
                $value = (int) $value;
            } elseif ($propertyType === 'string') {
                // Should already be a string.
            } elseif ($propertyType === 'self::LOGGER_LEVEL_*') {
                if (!in_array($value, self::ALLOWED_LOGGER_LEVELS, true)) {
                    throw new Exception("Unsupported value “{$value}” for property “{$propertyName}”, must be one of " . implode(', ', self::ALLOWED_LOGGER_LEVELS) . '.', 1);
                }
            } else {
                throw new Exception("Unknown type “{$propertyType}” for property “{$propertyName}”.", 1);
            }

            $this->{$propertyName} = $value;
            $this->modifiedOptions[$propertyName] = true;
        }

        // Interpolate variables in the config values.
        $datadir = $this->datadir;
        foreach (self::INTERPOLATED_PROPERTIES as $property) {
            $value = $this->{$property};
            $this->{$property} = str_replace('%datadir%', $datadir, $value);
        }
    }

    /**
     * Checks whether given configuration option has been changed.
     */
    public function isChanged(string $key): bool {
        return isset($this->modifiedOptions[$key]);
    }
}
