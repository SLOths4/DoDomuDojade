<?php

namespace App\Tests\Application\Display;

use App\Application\Display\GetDeparturesUseCase;
use App\Infrastructure\ExternalApi\Tram\TramService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GetDeparturesUseCaseTest extends TestCase
{
    public function testExecuteHandlesInvalidDepartureFormat(): void
    {
        $tramService = $this->createMock(TramService::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        $tramService->method('getTimes')->willReturn([
            'times' => [
                ['line' => '1'] // brak minutes i direction
            ]
        ]);

        $useCase = new GetDeparturesUseCase($tramService, $logger);
        
        // Chcemy sprawdzić czy nie rzuci Undefined Array Key
        $result = $useCase->execute(['DLA01']);
        
        // Jeśli nie rzuci błędu, to test przejdzie. 
        // W idealnym świecie ten element powinien zostać pominięty lub obsłużony bezpiecznie.
        $this->assertEmpty($result);
    }
}
