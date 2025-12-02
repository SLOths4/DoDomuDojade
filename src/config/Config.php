<?php
declare(strict_types=1);

namespace App\config;

use Exception;
use App\Infrastructure\Exception\ConfigException;

final readonly class Config
{

    public function __construct(
        public string $imgwWeatherUrl,
        public string $airlyEndpoint,
        public string $airlyApiKey,
        public string $airlyLocationId,
        public string $announcementsTableName,
        public string $announcementsDateFormat,
        public int    $announcementsMaxTitleLength,
        public int    $announcementsMaxTextLength,
        public string $modulesTableName,
        public string $modulesDateFormat,
        public string $countdownsTableName,
        public int $countdownsMaxTitleLength,
        public string $countdownsDateFormat,
        public string $usersTableName,
        public string $usersDateFormat,
        public int $maxUsernameLength,
        public int $minPasswordLength,
        public string $tramUrl,
        public array  $stopsIDs,
        public string $icalUrl,
        public string $quoteApiUrl,
        public string $quoteDateFormat,
        public string $quoteTableName,
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
            // Weather
            $imgw = self::env('IMGW_WEATHER_URL');
            $airly = self::env('AIRLY_ENDPOINT');
            $key = self::env('AIRLY_API_KEY');
            $loc = ltrim(self::env('AIRLY_LOCATION_ID', ''), '/');

            // Announcements
            $announcementsTableName = self::env('ANNOUNCEMENTS_TABLE_NAME', 'announcements');
            $announcementsDateFormat = self::env('ANNOUNCEMENTS_DATE_FORMAT', 'Y-m-d');
            $announcementsMaxTitleLength = (int)self::env('ANNOUNCEMENTS_MAX_TITLE_LENGTH', 255);
            $announcementsMaxTextLength = (int)self::env('ANNOUNCEMENTS_MAX_TEXT_LENGTH', 65535);

            // Countdowns
            $countdownsTableName = self::env('COUNTDOWN_TABLE_NAME', 'countdowns');
            $countdownsMaxTitleLength = (int)self::env('COUNTDOWN_MAX_TITLE_LENGTH', 255);
            $countdownsDateFormat = self::env('COUNTDOWNS_DATE_FORMAT', 'Y-m-d H:i:s');

            // Modules
            $modulesTableName = self::env('MODULES_TABLE_NAME', 'modules');
            $modulesDateFormat = self::env('MODULES_DATE_FORMAT', 'H:i');

            // Users
            $usersTableName = self::env('USERS_TABLE_NAME', 'users');
            $usersDateFormat = self::env('USER_DATE_FORMAT', 'Y-m-d');
            $maxUsernameLength = (int)self::env('MAX_USERNAME_LENGTH', 255);
            $minPasswordLength = (int)self::env('MIN_PASSWORD_LENGTH', 8);

            // Tram
            $tramUrl = self::env('TRAM_URL');
            $stopsIDs = self::env('STOPS_IDS');

            // Calendar
            $icalUrl = self::env('ICAL_URL', '');

            // Quote
            $quoteApiUrl = self::env('QUOTE_API_URL');
            $quoteDateFormat = self::env('QUOTE_DATE_FORMAT', 'Y-m-d');
            $quoteTableName = self::env('QUOTE_TABLE_NAME', 'quotes');

            $dbDsn = self::env('DB_DSN', self::env('DB_HOST'));
            $dbUsername = self::env('DB_USERNAME', '');
            $dbPassword = self::env('DB_PASSWORD', '');

            return new self(
                $imgw,
                $airly,
                $key,
                $loc,
                $announcementsTableName,
                $announcementsDateFormat,
                $announcementsMaxTitleLength,
                $announcementsMaxTextLength,
                $modulesTableName,
                $modulesDateFormat,
                $countdownsTableName,
                $countdownsMaxTitleLength,
                $countdownsDateFormat,
                $usersTableName,
                $usersDateFormat,
                $maxUsernameLength,
                $minPasswordLength,
                $tramUrl,
                explode(',', (string)$stopsIDs),
                $icalUrl,
                $quoteApiUrl,
                $quoteDateFormat,
                $quoteTableName,
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