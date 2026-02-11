<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Domain\Weather\WeatherRepositoryInterface;
use App\Domain\Weather\WeatherSnapshot;
use App\Infrastructure\ExternalApi\Weather\WeatherService;
use DateTimeImmutable;
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
        private WeatherRepositoryInterface $weatherRepository,
        private LoggerInterface $logger,
        private int $cacheTtlSeconds,
    ) {}

    /**
     * @return string[]|null
     */
    public function execute(): ?array
    {
        $cached = $this->weatherRepository->fetchLatest();

        if ($cached !== null && !empty($cached->payload) && $this->isCacheFresh($cached->fetchedOn)) {
            $this->logger->info('Returning cached weather data', [
                'fetched_on' => $cached->fetchedOn->format(DateTimeImmutable::ATOM),
            ]);
            return $this->formatDisplay($cached->payload);
        }

        try {
            $weatherData = $this->weatherService->getWeather();
        } catch (Exception $e) {
            $this->logger->error("Failed to fetch weather data", ['error' => $e->getMessage()]);
            if ($cached !== null && !empty($cached->payload)) {
                $this->logger->warning('Returning stale cached weather data after fetch failure', [
                    'error' => $e->getMessage(),
                    'fetched_on' => $cached->fetchedOn->format(DateTimeImmutable::ATOM),
                ]);
                return $this->formatDisplay($cached->payload);
            }

            return null;
        }

        if (empty($weatherData)) {
            return null;
        }

        try {
            $this->weatherRepository->add(new WeatherSnapshot(
                payload: $weatherData,
                fetchedOn: new DateTimeImmutable('now')
            ));
        } catch (Exception $e) {
            $this->logger->warning('Failed to persist weather cache', ['error' => $e->getMessage()]);
        }

        return $this->formatDisplay($weatherData);
    }

    private function formatDisplay(array $weatherData): ?array
    {
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

    private function isCacheFresh(?DateTimeImmutable $fetchedOn): bool
    {
        if ($fetchedOn === null || $this->cacheTtlSeconds <= 0) {
            return false;
        }

        $ageSeconds = (new DateTimeImmutable('now'))->getTimestamp() - $fetchedOn->getTimestamp();
        return $ageSeconds >= 0 && $ageSeconds < $this->cacheTtlSeconds;
    }
}
