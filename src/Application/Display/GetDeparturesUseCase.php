<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Infrastructure\ExternalApi\Tram\TramApiException;
use App\Infrastructure\ExternalApi\Tram\TramService;
use Psr\Log\LoggerInterface;

/**
 * Provides tram data formatted for display page
 */
readonly class GetDeparturesUseCase
{
    /**
     * @param TramService $tramService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private TramService $tramService,
        private LoggerInterface $logger
    ) {}

    /**
     * @param string[] $stopIds
     * @return array<int, array{stopId: string, line: string, minutes: int, direction: string}>
     */
    public function execute(array $stopIds): array
    {
        if (empty($stopIds)) {
            $this->logger->warning("No stop IDs configured — check STOP_ID env var");
            return [];
        }

        $departures = [];
        foreach ($stopIds as $stopId) {
            try {
                $stopDepartures = $this->tramService->getTimes($stopId);
            } catch (TramApiException $e) {
                $this->logger->warning("No departures found for stop", [
                    'stopId' => $stopId,
                    'reason' => $e->getMessage(),
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

        return $departures;
    }
}
