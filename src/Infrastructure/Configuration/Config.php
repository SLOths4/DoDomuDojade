<?php
declare(strict_types=1);

namespace App\Infrastructure\Configuration;

use Throwable;

/**
 * Wrapper class around the .env file
 */
final readonly class Config
{
    /**
     * @param string $loggingDirectoryPath
     * @param string $loggingChannelName
     * @param string $loggingLevel
     * @param string $twigCachePath
     * @param bool   $twigDebug
     * @param string $imgwWeatherUrl
     * @param string $airlyEndpoint
     * @param string $airlyApiKey
     * @param string $airlyLocationId
     * @param string $weatherTableName
     * @param string $weatherDateFormat
     * @param int    $weatherCacheTtlSeconds
     * @param string $dbHost
     * @param string $dbPort
     * @param string $dbName
     * @param string $dbUsername
     * @param string $dbPassword
     * @param string $announcementTableName
     * @param string $announcementDateFormat
     * @param string $announcementMaxValidDate
     * @param string $announcementDefaultValidDate
     * @param int    $announcementMaxTitleLength
     * @param int    $announcementMinTitleLength
     * @param int    $announcementMaxTextLength
     * @param int    $announcementMinTextLength
     * @param string $countdownTableName
     * @param string $countdownDateFormat
     * @param int    $countdownMaxTitleLength
     * @param string $moduleTableName
     * @param string $moduleDateFormat
     * @param string $userTableName
     * @param string $userDateFormat
     * @param int    $maxUsernameLength
     * @param int    $minPasswordLength
     * @param string $tramUrl
     * @param array  $stopID
     * @param string $googleCalendarApiKey
     * @param string $googleCalendarId
     * @param string $quoteApiUrl
     * @param string $quoteDateFormat
     * @param string $quoteTableName
     * @param string $wordApiUrl
     * @param string $wordTableName
     * @param string $wordDateFormat
     */
    private function __construct(
        // Logging
        public string $loggingDirectoryPath,
        public string $loggingChannelName,
        public string $loggingLevel,

        // Twig
        public string $twigCachePath,
        public bool $twigDebug,

        // Weather APIs
        public string $imgwWeatherUrl,
        public string $airlyEndpoint,
        public string $airlyApiKey,
        public string $airlyLocationId,

        // Weather persistence
        public string $weatherTableName,
        public string $weatherDateFormat,
        public int $weatherCacheTtlSeconds,

        // Database
        private string $dbHost,
        private string $dbPort,
        private string $dbName,
        private string $dbUsername,
        private string $dbPassword,

        // Announcements
        public string $announcementTableName,
        public string $announcementDateFormat,
        public string $announcementMaxValidDate,
        public string $announcementDefaultValidDate,
        public int $announcementMaxTitleLength,
        public int $announcementMinTitleLength,
        public int $announcementMaxTextLength,
        public int $announcementMinTextLength,

        // Countdowns
        public string $countdownTableName,
        public string $countdownDateFormat,
        public int $countdownMaxTitleLength,

        // Modules
        public string $moduleTableName,
        public string $moduleDateFormat,

        // Users
        public string $userTableName,
        public string $userDateFormat,
        public int $maxUsernameLength,
        public int $minPasswordLength,

        // Tram
        public string $tramUrl,
        public array $stopID,

        // Calendar
        public string $googleCalendarApiKey,
        public string $googleCalendarId,

        // Quote API
        public string $quoteApiUrl,
        public string $quoteDateFormat,
        public string $quoteTableName,

        // Word API
        public string $wordApiUrl,
        public string $wordTableName,
        public string $wordDateFormat,
    ) {}

    /**
     * @throws ConfigException
     */
    public static function fromEnv(): self
    {
        try {
            $stopIdString = self::optionalEnv('STOP_ID', '');

            return new self(
            // Logging
                loggingDirectoryPath: self::requiredEnv('LOGGING_DIRECTORY_PATH'),
                loggingChannelName: self::optionalEnv('LOGGING_CHANNEL_NAME', 'APP'),
                loggingLevel: self::optionalEnv('LOGGING_LEVEL', 'INFO'),

                // Twig
                twigCachePath: self::requiredEnv('TWIG_CACHE_PATH'),
                twigDebug: self::boolEnv('TWIG_DEBUG', false),

                // Weather
                imgwWeatherUrl: self::requiredEnv('IMGW_WEATHER_URL'),
                airlyEndpoint: self::requiredEnv('AIRLY_ENDPOINT'),
                airlyApiKey: self::requiredEnv('AIRLY_API_KEY'),
                airlyLocationId: ltrim(self::optionalEnv('AIRLY_LOCATION_ID', ''), '/'),

                // Weather persistence
                weatherTableName: self::optionalEnv('WEATHER_TABLE_NAME', 'weather'),
                weatherDateFormat: self::optionalEnv('WEATHER_DATE_FORMAT', 'Y-m-d H:i:s'),
                weatherCacheTtlSeconds: self::intEnv('WEATHER_CACHE_TTL_SECONDS', 900),

                // Database
                dbHost: self::optionalEnv('DB_HOST', 'localhost'),
                dbPort: self::optionalEnv('DB_PORT', '5432'),
                dbName: self::optionalEnv('DB_NAME', 'dodomudojade'),
                dbUsername: self::optionalEnv('DB_USERNAME', ''),
                dbPassword: self::optionalEnv('DB_PASSWORD', ''),

                // Announcements
                announcementTableName: self::optionalEnv('ANNOUNCEMENT_TABLE_NAME', 'announcement'),
                announcementDateFormat: self::optionalEnv('ANNOUNCEMENT_DATE_FORMAT', 'Y-m-d'),
                announcementMaxValidDate: self::optionalEnv('ANNOUNCEMENT_MAX_VALID_DATE', '+1 year'),
                announcementDefaultValidDate: self::optionalEnv('ANNOUNCEMENT_DEFAULT_VALID_DATE', '+30 days'),
                announcementMaxTitleLength: self::intEnv('ANNOUNCEMENT_MAX_TITLE_LENGTH', 255),
                announcementMinTitleLength: self::intEnv('ANNOUNCEMENT_MIN_TITLE_LENGTH', 5),
                announcementMaxTextLength: self::intEnv('ANNOUNCEMENT_MAX_TEXT_LENGTH', 65535),
                announcementMinTextLength: self::intEnv('ANNOUNCEMENT_MIN_TEXT_LENGTH', 10),

                // Countdowns
                countdownTableName: self::optionalEnv('COUNTDOWN_TABLE_NAME', 'countdown'),
                countdownDateFormat: self::optionalEnv('COUNTDOWN_DATE_FORMAT', 'Y-m-d H:i:s'),
                countdownMaxTitleLength: self::intEnv('COUNTDOWN_MAX_TITLE_LENGTH', 255),

                // Modules
                moduleTableName: self::optionalEnv('MODULE_TABLE_NAME', 'module'),
                moduleDateFormat: self::optionalEnv('MODULE_DATE_FORMAT', 'H:i:s'),

                // Users
                userTableName: self::optionalEnv('USER_TABLE_NAME', 'user'),
                userDateFormat: self::optionalEnv('USER_DATE_FORMAT', 'Y-m-d'),
                maxUsernameLength: self::intEnv('MAX_USERNAME_LENGTH', 255),
                minPasswordLength: self::intEnv('MIN_PASSWORD_LENGTH', 8),

                // Tram
                tramUrl: self::requiredEnv('TRAM_URL'),
                stopID: array_values(array_filter(
                    array_map('trim', explode(',', $stopIdString)),
                    static fn(string $v): bool => $v !== ''
                )),

                // Calendar
                googleCalendarApiKey: self::requiredEnv('CALENDAR_API_KEY_PATH'),
                googleCalendarId: self::requiredEnv('CALENDAR_ID'),

                // Quote
                quoteApiUrl: self::requiredEnv('QUOTE_API_URL'),
                quoteDateFormat: self::optionalEnv('QUOTE_DATE_FORMAT', 'Y-m-d'),
                quoteTableName: self::optionalEnv('QUOTE_TABLE_NAME', 'quote'),

                // Word
                wordApiUrl: self::requiredEnv('WORD_API_URL'),
                wordTableName: self::optionalEnv('WORD_TABLE_NAME', 'word'),
                wordDateFormat: self::optionalEnv('WORD_DATE_FORMAT', 'Y-m-d'),
            );

        } catch (ConfigException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ConfigException::loadingFailed($e);
        }
    }

    /**
     * Fetches required variable from .env and throws error if not found
     * @param string $key
     * @return string
     * @throws ConfigException
     */
    private static function requiredEnv(string $key): string
    {
        $value = self::fetchEnv($key);

        if ($value === null || $value === '') {
            throw ConfigException::missingVariable($key);
        }

        return $value;
    }

    /**
     * Returns default value if variable is not found in .env
     * @param string $key
     * @param string $default
     * @return string
     */
    private static function optionalEnv(string $key, string $default): string
    {
        return self::fetchEnv($key) ?? $default;
    }

    /**
     * Fetches integer variable type from .env
     * @param string $key
     * @param int $default
     * @return int
     */
    private static function intEnv(string $key, int $default): int
    {
        $value = self::fetchEnv($key);
        return $value !== null ? (int)$value : $default;
    }

    /**
     * Fetches boolean variable type from .env
     * @param string $key
     * @param bool $default
     * @return bool
     */
    private static function boolEnv(string $key, bool $default): bool
    {
        $value = self::fetchEnv($key);

        if ($value === null) {
            return $default;
        }

        return match (strtolower($value)) {
            'true', '1', 'yes', 'on' => true,
            'false', '0', 'no', 'off' => false,
            default => $default,
        };
    }

    /**
     * Fetches variable from .env file
     * @param string $key
     * @return string|null
     */
    private static function fetchEnv(string $key): ?string
    {
        $value = getenv($key);
        if (is_string($value) && $value !== '') {
            return trim($value);
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        if ($value !== null && $value !== '') {
            return trim((string)$value);
        }

        return null;
    }

    /**
     * @return string
     */
    public function dbDsn(): string
    {

        return "pgsql:host=" . $this->dbHost . ";port=" . $this->dbPort . ";dbname=" . $this->dbName;
    }

    /**
     * @return string
     */
    public function dbUsername(): string {
        return $this->dbUsername;
    }

    /**
     * @return string
     */
    public function dbPassword(): string {
        return $this->dbPassword;
    }
}
