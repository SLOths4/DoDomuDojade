<?php
declare(strict_types=1);

namespace App\config;

use Exception;
use App\Infrastructure\Exception\ConfigException;

final readonly class Config
{

    public function __construct(
        public string $loggingDirectoryPath,
        public string $loggingChannelName,
        public string $loggingLevel,
        public string $imgwWeatherUrl,
        public string $airlyEndpoint,
        public string $airlyApiKey,
        public string $airlyLocationId,
        public string $announcementTableName,
        public string $announcementDateFormat,
        public int    $announcementMaxTitleLength,
        public int    $announcementMaxTextLength,
        public string $moduleTableName,
        public string $moduleDateFormat,
        public string $countdownTableName,
        public int    $countdownMaxTitleLength,
        public string $countdownDateFormat,
        public string $userTableName,
        public string $userDateFormat,
        public int    $maxUsernameLength,
        public int    $minPasswordLength,
        public string $tramUrl,
        public array  $stopID,
        public string $icalUrl,
        public string $quoteApiUrl,
        public string  $quoteDateFormat,
        public string  $quoteTableName,
        public string  $wordApiUrl,
        public string  $wordTableName,
        public string  $wordDateFormat,
        private string $dbDsn,
        private string $dbUsername,
        private string $dbPassword,
    ) {}

    /**
     * @throws Exception
     */
    public static function fromEnv(): self
    {
        try {
            // Logging
            $loggingDirectoryPath = self::env('LOGGING_DIRECTORY_PATH');
            $loggingChannelName = self::env('LOGGING_CHANNEL_NAME', 'APP');
            $loggingLevel = self::env('LOGGING_LEVEL', 'INFO');

            // Weather
            $imgw = self::env('IMGW_WEATHER_URL');
            $airly = self::env('AIRLY_ENDPOINT');
            $key = self::env('AIRLY_API_KEY');
            $loc = ltrim(self::env('AIRLY_LOCATION_ID', ''), '/');

            // Announcements
            $announcementTableName = self::env('ANNOUNCEMENT_TABLE_NAME', 'announcement');
            $announcementDateFormat = self::env('ANNOUNCEMENT_DATE_FORMAT', 'Y-m-d');
            $announcementMaxTitleLength = (int)self::env('ANNOUNCEMENT_MAX_TITLE_LENGTH', 255);
            $announcementMaxTextLength = (int)self::env('ANNOUNCEMENT_MAX_TEXT_LENGTH', 65535);

            // Countdowns
            $countdownTableName = self::env('COUNTDOWN_TABLE_NAME', 'countdown');
            $countdownMaxTitleLength = (int)self::env('COUNTDOWN_MAX_TITLE_LENGTH', 255);
            $countdownDateFormat = self::env('COUNTDOWN_DATE_FORMAT', 'Y-m-d H:i:s');

            // Modules
            $moduleTableName = self::env('MODULE_TABLE_NAME', 'module');
            $moduleDateFormat = self::env('MODULE_DATE_FORMAT', 'H:i');

            // Users
            $userTableName = self::env('USER_TABLE_NAME', 'user');
            $userDateFormat = self::env('USER_DATE_FORMAT', 'Y-m-d');
            $maxUsernameLength = (int)self::env('MAX_USERNAME_LENGTH', 255);
            $minPasswordLength = (int)self::env('MIN_PASSWORD_LENGTH', 8);

            // Tram
            $tramUrl = self::env('TRAM_URL');
            $stopID = self::env('STOP_ID', '');

            // Calendar
            $icalUrl = self::env('ICAL_URL', '');

            // Quote
            $quoteApiUrl = self::env('QUOTE_API_URL');
            $quoteDateFormat = self::env('QUOTE_DATE_FORMAT', 'Y-m-d');
            $quoteTableName = self::env('QUOTE_TABLE_NAME', 'quote');

            // Word
            $wordApiUrl = self::env('WORD_API_URL');
            $wordTableName = self::env('WORD_TABLE_NAME', 'word');
            $wordDateFormat = self::env('WORD_DATE_FORMAT', 'Y-m-d');


            $dbDsn = self::env('DB_DSN');
            $dbUsername = self::env('DB_USERNAME', '');
            $dbPassword = self::env('DB_PASSWORD', '');

            return new self(
                $loggingDirectoryPath,
                $loggingChannelName,
                $loggingLevel,
                $imgw,
                $airly,
                $key,
                $loc,
                $announcementTableName,
                $announcementDateFormat,
                $announcementMaxTitleLength,
                $announcementMaxTextLength,
                $moduleTableName,
                $moduleDateFormat,
                $countdownTableName,
                $countdownMaxTitleLength,
                $countdownDateFormat,
                $userTableName,
                $userDateFormat,
                $maxUsernameLength,
                $minPasswordLength,
                $tramUrl,
                array_values(array_filter(array_map('trim', explode(',', $stopID)), static fn(string $v): bool => $v !== '')),
                $icalUrl,
                $quoteApiUrl,
                $quoteDateFormat,
                $quoteTableName,
                $wordApiUrl,
                $wordTableName,
                $wordDateFormat,
                $dbDsn,
                $dbUsername,
                $dbPassword
            );
        } catch (ConfigException $e) {
            throw $e;
        } catch (Exception $e) {
            throw ConfigException::loadingFailed($e);
        }
    }

    /**
     * @throws Exception
     */
    private static function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === false) {
            $value = $_ENV[$key] ?? ($_SERVER[$key] ?? (function_exists('apache_getenv') ? apache_getenv($key) : null));
        }

        if ($value === false || $value === null) {
            return $default;
        }

        if ($value === '') {
            if ($default === null) {
                throw ConfigException::missingVariable($key);
            } else {
                return $default;
            }
        }

        return $value;
    }
    public function dbDsn(): string { return $this->dbDsn; }
    public function dbUsername(): string { return $this->dbUsername; }
    public function dbPassword(): string { return $this->dbPassword; }
}