<?php
declare(strict_types=1);

namespace App\config;

use App\Domain\Exception\ConfigException;
use Exception;

/**
 * Class with configuration variables fetched from .env
 */
final readonly class Config
{
    /**
     * @param string $loggingDirectoryPath
     * @param string $loggingChannelName
     * @param string $loggingLevel
     * @param string $imgwWeatherUrl
     * @param string $airlyEndpoint
     * @param string $airlyApiKey
     * @param string $airlyLocationId
     * @param string $announcementTableName
     * @param string $announcementDateFormat
     * @param string $announcementMaxValidDate Datetime modifier valid modifiers at: {@link https://www.php.net/manual/en/datetime.formats.php}
     * @param string $announcementDefaultValidDate Datetime modifier valid modifiers at: {@link https://www.php.net/manual/en/datetime.formats.php}
     * @param int $announcementMaxTitleLength
     * @param int $announcementMinTitleLength
     * @param int $announcementMaxTextLength
     * @param int $announcementMinTextLength
     * @param string $moduleTableName
     * @param string $moduleDateFormat
     * @param string $countdownTableName
     * @param int $countdownMaxTitleLength
     * @param string $countdownDateFormat
     * @param string $userTableName
     * @param string $userDateFormat
     * @param int $maxUsernameLength
     * @param int $minPasswordLength
     * @param string $tramUrl
     * @param array $stopID
     * @param string $icalUrl
     * @param string $quoteApiUrl
     * @param string $quoteDateFormat
     * @param string $quoteTableName
     * @param string $wordApiUrl
     * @param string $wordTableName
     * @param string $wordDateFormat
     * @param string $dbDsn
     * @param string $dbUsername
     * @param string $dbPassword
     */
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
        public string $announcementMaxValidDate,
        public string $announcementDefaultValidDate,
        public int    $announcementMaxTitleLength,
        public int    $announcementMinTitleLength,
        public int    $announcementMaxTextLength,
        public int    $announcementMinTextLength,
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
            $announcementMaxValidDate = self::env('ANNOUNCEMENT_MAX_VALID_DATE', '+1 year') ;
            $announcementDefaultValidDate = self::env('ANNOUNCEMENT_DEFAULT_VALID_DATE', '+30 days') ;
            $announcementMaxTitleLength = (int)self::env('ANNOUNCEMENT_MAX_TITLE_LENGTH', 255);
            $announcementMinTitleLength = (int)self::env('ANNOUNCEMENT_MIN_TEXT_LENGTH', 5);
            $announcementMaxTextLength = (int)self::env('ANNOUNCEMENT_MAX_TEXT_LENGTH', 65535);
            $announcementMinTextLength = (int)self::env('ANNOUNCEMENT_MIN_TEXT_LENGTH', 10);

            // Countdowns
            $countdownTableName = self::env('COUNTDOWN_TABLE_NAME', 'countdown');
            $countdownMaxTitleLength = (int)self::env('COUNTDOWN_MAX_TITLE_LENGTH', 255);
            $countdownDateFormat = self::env('COUNTDOWN_DATE_FORMAT', 'Y-m-d H:i:s');

            // Modules
            $moduleTableName = self::env('MODULE_TABLE_NAME', 'module');
            static $moduleDateformat = 'H:i:s';

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

            // Database
            $dbDsn = self::env('DB_DSN');
            $dbUsername = self::env('DB_USERNAME', '');
            $dbPassword = self::env('DB_PASSWORD', '');

            return new self(
                loggingDirectoryPath: $loggingDirectoryPath,
                loggingChannelName: $loggingChannelName,
                loggingLevel: $loggingLevel,
                imgwWeatherUrl: $imgw,
                airlyEndpoint: $airly,
                airlyApiKey: $key,
                airlyLocationId: $loc,
                announcementTableName: $announcementTableName,
                announcementDateFormat: $announcementDateFormat,
                announcementMaxValidDate: $announcementMaxValidDate,
                announcementDefaultValidDate: $announcementDefaultValidDate,
                announcementMaxTitleLength: $announcementMaxTitleLength,
                announcementMinTitleLength: $announcementMinTitleLength,
                announcementMaxTextLength: $announcementMaxTextLength,
                announcementMinTextLength: $announcementMinTextLength,
                moduleTableName: $moduleTableName,
                moduleDateFormat: $moduleDateformat,
                countdownTableName: $countdownTableName,
                countdownMaxTitleLength: $countdownMaxTitleLength,
                countdownDateFormat: $countdownDateFormat,
                userTableName: $userTableName,
                userDateFormat: $userDateFormat,
                maxUsernameLength: $maxUsernameLength,
                minPasswordLength: $minPasswordLength,
                tramUrl: $tramUrl,
                stopID: array_values(array_filter(array_map('trim', explode(',', $stopID)), static fn(string $v): bool => $v !== '')),
                icalUrl: $icalUrl,
                quoteApiUrl: $quoteApiUrl,
                quoteDateFormat: $quoteDateFormat,
                quoteTableName: $quoteTableName,
                wordApiUrl: $wordApiUrl,
                wordTableName: $wordTableName,
                wordDateFormat: $wordDateFormat,
                dbDsn: $dbDsn,
                dbUsername: $dbUsername,
                dbPassword: $dbPassword
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