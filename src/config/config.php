<?php
declare(strict_types=1);

namespace src\config;

final readonly class config
{

    public function __construct(
        private string $imgwWeatherUrl,
        private string $airlyEndpoint,
        private string $airlyApiKey,
        private string $airlyLocationId,
        private string $announcementsTableName,
        private string $announcementsDateFormat,
        private int    $announcementsMaxTitleLength,
        private int    $announcementsMaxTextLength,
        private array $announcementsTableColumns,
        private string $modulesTableName,
        private array $modulesTableColumns,
        private string $modulesDateFormat,
        private string $countdownsTableName,
        private array $countdownsTableColumns,
        private int $countdownsMaxTitleLength,
        private string $countdownsDateFormat,
        private string $usersTableName,
        private string $userDateFormat,
        private int $maxUsernameLength,
        private int $minPasswordLength,
        private string $tramURL,
        private array  $stopsIDs,
        private string $icalURL,
        private string $dbDsn,
        private string $dbUsername,
        private string $dbPassword,
    ) {}

    public static function fromEnv(): self
    {
        // Weather
        $imgw = self::env('IMGW_WEATHER_URL', '');
        $airly = self::env('AIRLY_ENDPOINT', '');
        $key = self::env('AIRLY_API_KEY', '');
        $loc = ltrim(self::env('AIRLY_LOCATION_ID', ''), '/');

        // Announcements
        $announcementsTableName = self::env('ANNOUNCEMENTS_TABLE_NAME', 'announcements');
        $announcementsDateFormat = self::env('ANNOUNCEMENTS_DATE_FORMAT', 'Y-m-d');
        $announcementsMaxTitleLength = (int) self::env('ANNOUNCEMENTS_MAX_TITLE_LENGTH', 255);
        $announcementsMaxTextLength = (int) self::env('ANNOUNCEMENTS_MAX_TEXT_LENGTH', 65535);
        $announcementsTableColumns = self::env('ANNOUNCEMENTS_TABLE_COLUMNS', 'title,text,date,valid_until,user_id');

        // Countdowns
        $countdownsTableName = self::env('COUNTDOWN_TABLE_NAME', 'countdowns');
        $countdownsTableColumns = self::env('COUNTDOWN_TABLE_COLUMNS', 'title,text,date,valid_until,user_id');
        $countdownsMaxTitleLength = (int) self::env('COUNTDOWN_MAX_TITLE_LENGTH', 255);
        $countdownsDateFormat = self::env('COUNTDOWNS_DATE_FORMAT', 'Y-m-d H:i:s');

        // Modules
        $modulesTableName = self::env('MODULES_TABLE_NAME', 'modules');
        $modulesTableColumns = self::env('MODULES_TABLE_COLUMNS', 'name,enabled');
        $modulesDateFormat = self::env('MODULES_DATE_FORMAT', 'H:i');

        // Users
        $usersTableName = self::env('USERS_TABLE_NAME', 'users');
        $userDateFormat = self::env('USER_DATE_FORMAT', 'Y-m-d');
        $maxUsernameLength = (int) self::env('MAX_USERNAME_LENGTH', 255);
        $minPasswordLength = (int) self::env('MIN_PASSWORD_LENGTH', 8);

        // Tram
        $tramURL = self::env('TRAM_URL', '');
        $stopsIDs = self::env('STOPS_IDS', '');

        // Calendar
        $icalURL = self::env('ICAL_URL', '');

        $dbDsn = self::env('DB_DSN', self::env('DB_HOST', ''));
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
            explode(',', $announcementsTableColumns),
            $modulesTableName,
            explode(',', $modulesTableColumns),
            $modulesDateFormat,
            $countdownsTableName,
            explode(',', $countdownsTableColumns),
            $countdownsMaxTitleLength,
            $countdownsDateFormat,
            $usersTableName,
            $userDateFormat,
            $maxUsernameLength,
            $minPasswordLength,
            $tramURL,
            explode(',', $stopsIDs),
            $icalURL,
            $dbDsn,
            $dbUsername,
            $dbPassword
        );
    }

    private static function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? ($_SERVER[$key] ?? (function_exists('apache_getenv') ? apache_getenv($key) : null));
        }

        if ($value === false || $value === null) {
            return $default;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            return $default;
        }

        return $value;
    }

    public function imgwWeatherUrl(): string { return $this->imgwWeatherUrl; }
    public function airlyEndpoint(): string { return $this->airlyEndpoint; }
    public function airlyApiKey(): string { return $this->airlyApiKey; }
    public function airlyLocationId(): string { return $this->airlyLocationId; }
    public function announcementsTableName(): string { return $this->announcementsTableName; }
    public function announcementsDateFormat(): string { return $this->announcementsDateFormat; }
    public function announcementsMaxTitleLength(): int { return $this->announcementsMaxTitleLength; }
    public function announcementsMaxTextLength(): int { return $this->announcementsMaxTextLength; }
    public function announcementsTableColumns(): array { return $this->announcementsTableColumns; }
    public function moduleTableName(): string { return $this->modulesTableName; }
    public function moduleTableColumns(): array { return $this->modulesTableColumns; }
    public function modulesDateFormat(): string { return $this->modulesDateFormat; }
    public function countdownsTableName(): string { return $this->countdownsTableName; }
    public function userTableName(): string { return $this->usersTableName; }
    public function userDateFormat(): string { return $this->userDateFormat; }
    public function maxUsernameLength(): int { return $this->maxUsernameLength; }
    public function minPasswordLength(): int { return $this->minPasswordLength; }
    public function countdownsTableColumns(): array { return $this->countdownsTableColumns; }
    public function countdownsMaxTitleLength(): int { return $this->countdownsMaxTitleLength; }
    public function countdownsDateFormat(): string { return $this->countdownsDateFormat; }
    public function tramURL(): string { return $this->tramURL; }
    public function stopsIDs(): array { return $this->stopsIDs; }
    public function icalURL(): string { return $this->icalURL; }
    public function dbDsn(): string { return $this->dbDsn; }
    public function dbUsername(): string { return $this->dbUsername; }
    public function dbPassword(): string { return $this->dbPassword; }
}