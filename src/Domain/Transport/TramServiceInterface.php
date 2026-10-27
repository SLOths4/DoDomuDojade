<?php
declare(strict_types=1);

namespace App\Domain\Transport;

/**
 * Interface for tram data services
 */
interface TramServiceInterface
{
    /**
     * @param string $stopId
     * @return array<string, mixed>
     */
    public function getTimes(string $stopId): array;

    /**
     * @param float $lat
     * @param float $lon
     * @return array<string, mixed>
     */
    public function getStops(float $lat, float $lon): array;

    /**
     * @param int $lineNumber
     * @return array<string, mixed>
     */
    public function getLines(int $lineNumber): array;

    /**
     * @param int $lineNumber
     * @return array<string, mixed>
     */
    public function getRoutes(int $lineNumber): array;

    /**
     * @param string $bollardSymbol
     * @return array<string, mixed>
     */
    public function getMessageForBollard(string $bollardSymbol): array;
}
