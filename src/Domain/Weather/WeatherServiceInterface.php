<?php
declare(strict_types=1);

namespace App\Domain\Weather;

/**
 * Interface for weather services
 */
interface WeatherServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getWeather(): array;
}
