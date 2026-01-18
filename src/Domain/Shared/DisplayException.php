<?php
namespace App\Domain\Shared;

/**
 * Display module exceptions - contains translation KEYS
 */
final class DisplayException extends DomainException
{
    /**
     * Module is not visible/active
     */
    public static function moduleNotVisible(string $module): self
    {
        return new self(
            'display.module_not_visible',
            DomainExceptionCodes::DISPLAY_MODULE_NOT_VISIBLE->value
        );
    }

    /**
     * Failed to fetch tram departure data
     */
    public static function failedToFetchDepartures(): self
    {
        return new self(
            'display.fetch_departures_failed',
            DomainExceptionCodes::DISPLAY_FETCH_DEPARTURES_FAILED->value
        );
    }

    /**
     * Failed to fetch announcements
     */
    public static function failedToFetchAnnouncements(): self
    {
        return new self(
            'display.fetch_announcements_failed',
            DomainExceptionCodes::DISPLAY_FETCH_ANNOUNCEMENTS_FAILED->value
        );
    }

    /**
     * Failed to fetch countdown
     */
    public static function failedToFetchCountdown(): self
    {
        return new self(
            'display.fetch_countdown_failed',
            DomainExceptionCodes::DISPLAY_FETCH_COUNTDOWN_FAILED->value
        );
    }

    /**
     * Failed to fetch weather data
     */
    public static function failedToFetchWeather(): self
    {
        return new self(
            'display.fetch_weather_failed',
            DomainExceptionCodes::DISPLAY_FETCH_WEATHER_FAILED->value
        );
    }

    /**
     * Failed to fetch quote
     */
    public static function failedToFetchQuote(): self
    {
        return new self(
            'display.fetch_quote_failed',
            DomainExceptionCodes::DISPLAY_FETCH_QUOTE_FAILED->value
        );
    }

    /**
     * Failed to fetch word
     */
    public static function failedToFetchWord(): self
    {
        return new self(
            'display.fetch_word_failed',
            DomainExceptionCodes::DISPLAY_FETCH_WORD_FAILED->value
        );
    }
}
