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
     * @param array $stopIds
     * @return array
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

            if (!isset($stopDepartures['times']) || !is_array($stopDepartures['times'])) {
                $this->logger->warning("Invalid departure data format", ['stopId' => $stopId]);
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
