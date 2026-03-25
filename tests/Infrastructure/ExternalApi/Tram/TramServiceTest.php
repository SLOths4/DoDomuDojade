<?php

namespace App\Tests\Infrastructure\ExternalApi\Tram;

use App\Infrastructure\ExternalApi\Tram\TramApiException;
use App\Infrastructure\ExternalApi\Tram\TramService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TramServiceTest extends TestCase
{
    private $logger;
    private $httpClient;
    private $tramService;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->tramService = new TramService(
            $this->logger,
            $this->httpClient,
            'https://test-ztm-url.pl'
        );
    }

    public function testGetStopsWithInvalidCoordinates(): void
    {
        $this->expectException(TramApiException::class);
        $this->expectExceptionMessage('Invalid GPS coordinates');
        
        // Te współrzędne są technicznie poprawne (-90 do 90, -180 do 180), 
        // ale chcemy, aby system rzucał błąd, bo są daleko poza Poznaniem (np. Nowy Jork)
        $this->tramService->getStops(40.7128, -74.0060);
    }

    public function testMakeApiRequestHandlesLogicError(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'success' => false,
            'error' => ['message' => 'Some API logic error']
        ]);

        $this->httpClient->method('request')->willReturn($response);

        $this->expectException(TramApiException::class);
        $this->expectExceptionMessage('ZTM API logic error: Some API logic error');
        
        $this->tramService->getTimes('DLA01');
    }
}
