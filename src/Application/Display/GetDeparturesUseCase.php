<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Infrastructure\ExternalApi\Tram\TramService;
use Exception;
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
        $departures = [];
        foreach ($stopIds as $stopId) {
            try {
                $stopDepartures = $this->tramService->getTimes($stopId);
            } catch (Exception) {
                $this->logger->warning("No departures found for stop", ['stopId' => $stopId]);
                continue;
            }

            // PHPStan: $stopDepartures is guaranteed to have 'times' key based on TramService::getTimes() PHPDoc
            // but we keep the checks for runtime safety if PHPDoc is not fully trusted by reality
            if (!isset($stopDepartures['times']) || !is_array($stopDepartures['times'])) {
                $this->logger->warning("Invalid departure data format", ['stopId' => $stopId]);
                continue;
            }

            foreach ($stopDepartures['times'] as $departure) {
                if (!isset($departure['line'], $departure['minutes'], $departure['direction'])) {
                    $this->logger->warning("Incomplete departure data", ['stopId' => $stopId, 'departure' => $departure]);
                    continue;
                }

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
