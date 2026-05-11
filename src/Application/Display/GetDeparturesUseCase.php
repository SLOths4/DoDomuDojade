<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Domain\Transport\TramServiceInterface;
use App\Infrastructure\ExternalApi\Tram\TramApiException;
use Psr\Log\LoggerInterface;

/**
 * Provides tram data formatted for display page
 */
readonly class GetDeparturesUseCase
{
    /**
     * @param TramServiceInterface $tramService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private TramServiceInterface $tramService,
        private LoggerInterface $logger
    ) {}

    /**
     * @param string[] $stopIds
     * @return array<int, array{stopId: string, line: string, minutes: int, direction: string}>
     */
    public function execute(array $stopIds): array
    {
        $this->logger->debug('Fetching departures for display', [
            'stop_count' => count($stopIds),
            'stop_ids' => $stopIds,
        ]);

        $departures = [];
        foreach ($stopIds as $stopId) {
            try {
                $stopDepartures = $this->tramService->getTimes($stopId);
            } catch (TramApiException $e) {
                $this->logger->warning('Failed to fetch departures for stop', [
                    'stop_id' => $stopId,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            foreach ($stopDepartures['times'] as $departure) {
                $departures[] = [
                    'stopId' => $stopId,
                    'line' => (string)$departure['line'],
                    'minutes' => (int)$departure['minutes'],
                    'direction' => (string)$departure['direction'],
                ];
            }
        }

        usort($departures, static fn($a, $b) => $a['minutes'] <=> $b['minutes']);

        $this->logger->debug('Departures fetched for display', [
            'stop_count' => count($stopIds),
            'departure_count' => count($departures),
        ]);

        return $departures;
    }
}
