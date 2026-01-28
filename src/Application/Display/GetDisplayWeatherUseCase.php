<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Infrastructure\ExternalApi\Weather\WeatherService;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Provides weather data formatted for display page
 */
readonly class GetDisplayWeatherUseCase
{
    /**
     * @param WeatherService $weatherService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private WeatherService $weatherService,
        private LoggerInterface $logger
    ) {}

    /**
     * @return string[]|null
     */
    public function execute(): ?array
    {
        try {
            $weatherData = $this->weatherService->getWeather();
        } catch (Exception $e) {
            $this->logger->error("Failed to fetch weather data", ['error' => $e->getMessage()]);
            return null;
        }

        if (empty($weatherData)) {
            return null;
        }

        return [
            'temperature' => (string)($weatherData['imgw_temperature'] ?? 'N/A'),
            'pressure' => (string)($weatherData['imgw_pressure'] ?? 'N/A'),
            'airlyAdvice' => (string)($weatherData['airly_index_advice'] ?? 'N/A'),
            'airlyDescription' => (string)($weatherData['airly_index_description'] ?? 'N/A'),
            'airlyColour' => (string)($weatherData['airly_index_colour'] ?? 'N/A')
        ];
    }
}
