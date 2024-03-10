<?php

declare(strict_types=1);

namespace helpers;

use Exception;
use ReflectionClass;
use ReflectionNamedType;

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
    private array $modifiedOptions = [];

    // Internal but overridable values.

    /** Debugging level @internal */
    public int $debug = 0;

    /** @internal */
    public string $datadir = __DIR__ . '/../../data';

    /** @internal */
    public string $cache = '%datadir%/cache';

    /** @internal */
    public string $ftrssCustomDataDir = '%datadir%/fulltextrss';

    // Rest of the values.

    public string $dbType = 'sqlite';

    public string $dbFile = '%datadir%/sqlite/selfoss.db';

    public string $dbHost = 'localhost';

    public string $dbDatabase = 'selfoss';

    public string $dbUsername = 'root';

    public string $dbPassword = '';

    public ?int $dbPort = null;

    public ?string $dbSocket = null;

    public string $dbPrefix = '';

    public string $loggerDestination = 'file:%datadir%/logs/default.log';

    /** @var self::LOGGER_LEVEL_* */
    public string $loggerLevel = 'ERROR';

    public int $itemsPerpage = 50;

    public int $itemsLifetime = 30;

    public string $baseUrl = '';

    public string $username = '';

    public string $password = '';

    public string $salt = 'lkjl1289';

    public bool $public = false;

    public string $htmlTitle = 'selfoss';

    public string $rssTitle = 'selfoss feed';

    public int $rssMaxItems = 300;

    public bool $rssMarkAsRead = false;

    public string $homepage = 'newest';

    public ?string $language = null;

    public bool $autoMarkAsRead = false;

    public bool $autoCollapse = false;

    public bool $autoStreamMore = true;

    public bool $openInBackgroundTab = false;

    public string $share = 'atfpde';

    public string $wallabag = '';

    public int $wallabagVersion = 2;

    public ?string $wordpress = null;

    public ?string $mastodon = null;

    public bool $allowPublicUpdateAccess = false;

    public string $unreadOrder = 'desc';

    public bool $loadImagesOnMobile = false;

    public bool $autoHideReadOnMobile = false;

    public string $envPrefix = 'selfoss_';

    public string $camoDomain = '';

    public string $camoKey = '';

    public bool $scrollToArticleHeader = true;

    public bool $showThumbnails = true;

    public bool $doubleClickMarkAsRead = false;

    public int $readingSpeedWpm = 0;

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

            $nullable = false;
            $propertyType = null;
            if (($doc = $property->getDocComment()) !== false && preg_match('(@var (?P<nullable>\??)(?P<type>[^\s]+))', $doc, $matches) === 1) {
                $nullable = $matches['nullable'] === '?';
                $propertyType = $matches['type'];
            } else {
                $type = $property->getType();
                $nullable = $type !== null && $type->allowsNull();
                if ($type instanceof ReflectionNamedType) {
                    $propertyType = $type->getName();
                }
            }

            if ($nullable && $value === '') {
                // Keep the default value for empty nullables.
                continue;
            }

            $propertyName = $property->getName();
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
