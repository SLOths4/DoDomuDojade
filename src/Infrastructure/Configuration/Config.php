<?php
declare(strict_types=1);

namespace App\Infrastructure\Configuration;

use Throwable;

final readonly class Config
{
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

        // Database
        private string $dbDsn,
        private string $dbUsername,
        private string $dbPassword,

        // Redis
        public string $redisHost,
        public int $redisPort,

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

                // Database
                dbDsn: self::requiredEnv('DB_DSN'),
                dbUsername: self::optionalEnv('DB_USERNAME', ''),
                dbPassword: self::optionalEnv('DB_PASSWORD', ''),

                // Redis
                redisHost: self::optionalEnv('REDIS_HOST', '127.0.0.1'),
                redisPort: self::intEnv('REDIS_PORT', 6379),

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

    private static function requiredEnv(string $key): string
    {
        $value = self::fetchEnv($key);

        if ($value === null || $value === '') {
            throw ConfigException::missingVariable($key);
        }

        return $value;
    }

    private static function optionalEnv(string $key, string $default): string
    {
        return self::fetchEnv($key) ?? $default;
    }

    private static function intEnv(string $key, int $default): int
    {
        $value = self::fetchEnv($key);
        return $value !== null ? (int)$value : $default;
    }

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

    public function dbDsn(): string
    {
        return $this->dbDsn;
    }

    public function dbUsername(): string
    {
        return $this->dbUsername;
    }

    public function dbPassword(): string
    {
        return $this->dbPassword;
    }
}
