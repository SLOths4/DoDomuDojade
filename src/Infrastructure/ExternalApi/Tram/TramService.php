<?php

namespace App\Infrastructure\ExternalApi\Tram;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * PEKA e-monitor API wrapper
 *
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
readonly class TramService
{
    public function __construct(
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
        private string $ztmUrl
    ) {}

    private function isValidCoordinates(float $lat, float $lon): bool
    {
        // Greater Poznań area:
        // Lat: 52.2 to 52.6 (approx.)
        // Lon: 16.7 to 17.1 (approx.)
        return $lat >= 52.0 && $lat <= 53.0 && $lon >= 16.0 && $lon <= 18.0;
    }

    protected function makeApiRequest(string $method, array $params): array
    {
        $this->logger->debug("Making ZTM API request", ['method' => $method]);

        $response = $this->httpClient->request(
            'POST',
            $this->ztmUrl,
            [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'method' => $method,
                    'p0' => json_encode($params),
                ],
            ]
        );

        $responseArray = $response->toArray();

        if (isset($responseArray['success']) && $responseArray['success'] === false) {
            $errorMessage = $responseArray['error']['message'] ?? 'Unknown API logic error';
            throw TramApiException::apiLogicError($errorMessage);
        }

        return $responseArray;
    }

    /**
     * Get departure times for a specific stop
     *
     * @param string $stopId Stop symbol (e.g., "DLA01")
     * @return array{times: array{line: string, minutes: int, direction: string}[]}
     * @throws TramApiException
     * @throws InvalidArgumentException
     */
    public function getTimes(string $stopId): array
    {
        try {
            if (!preg_match('/^[A-Z0-9]+$/', $stopId)) {
                throw TramApiException::invalidStopId($stopId);
            }

            $this->logger->debug("Fetching departure times", ['stopId' => $stopId]);

            $response = $this->makeApiRequest('getTimes', ['symbol' => $stopId]);

            if (!isset($response['success']) || !is_array($response['success'])) {
                throw TramApiException::noDepartureData($stopId);
            }

            if (!isset($response['success']['times']) || !is_array($response['success']['times'])) {
                $this->logger->info("No departure times in response", ['stopId' => $stopId]);
                return ['times' => []];
            }

            $this->logger->info("Departure times successfully fetched", ['stopId' => $stopId]);

            return $response['success'];

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('ZTM API transport error', ['stopId' => $stopId]);
            throw TramApiException::transportError($e);
        } catch (DecodingExceptionInterface $e) {
            $this->logger->error('ZTM API response decoding error', ['stopId' => $stopId]);
            throw TramApiException::decodingError($e);
        } catch (Throwable $e) {
            if ($e instanceof TramApiException) {
                throw $e;
            }
            $this->logger->error('Failed to fetch departure times', ['stopId' => $stopId, 'error' => $e->getMessage()]);
            throw TramApiException::apiCallFailed('getTimes', $e);
        }
    }

    /**
     * Get stops near specified GPS coordinates
     *
     * @param float $lat Latitude (-90 to 90)
     * @param float $lon Longitude (-180 to 180)
     * @return array
     * @throws TramApiException
     * @throws InvalidArgumentException
     */
    public function getStops(float $lat, float $lon): array
    {
        try {
            if (!$this->isValidCoordinates($lat, $lon)) {
                throw TramApiException::invalidCoordinates($lat, $lon);
            }

            $this->logger->debug("Fetching nearby stops", ['lat' => $lat, 'lon' => $lon]);

            $response = $this->makeApiRequest('getStops', ['lat' => $lat, 'lon' => $lon]);

            if (empty($response['success'])) {
                throw TramApiException::noStopsData($lat, $lon);
            }

            $this->logger->info("Nearby stops successfully fetched", ['lat' => $lat, 'lon' => $lon]);

            return $response['success'];

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('ZTM API transport error', ['lat' => $lat, 'lon' => $lon]);
            throw TramApiException::transportError($e);
        } catch (DecodingExceptionInterface $e) {
            $this->logger->error('ZTM API response decoding error', ['lat' => $lat, 'lon' => $lon]);
            throw TramApiException::decodingError($e);
        } catch (Throwable $e) {
            if ($e instanceof TramApiException) {
                throw $e;
            }
            $this->logger->error('Failed to fetch nearby stops', ['lat' => $lat, 'lon' => $lon, 'error' => $e->getMessage()]);
            throw TramApiException::apiCallFailed('getStops', $e);
        }
    }

    /**
     * Get line information
     *
     * @param int $lineNumber
     * @return array
     * @throws TramApiException
     * @throws InvalidArgumentException
     */
    public function getLines(int $lineNumber): array
    {
        try {
            if ($lineNumber <= 0) {
                throw TramApiException::invalidLineNumber($lineNumber);
            }

            $this->logger->debug("Fetching line information", ['lineNumber' => $lineNumber]);

            $response = $this->makeApiRequest('getLines', ['line' => $lineNumber]);

            $this->logger->info("Line information successfully fetched", ['lineNumber' => $lineNumber]);

            return $response;

        } catch (Throwable $e) {
            if ($e instanceof TramApiException) {
                throw $e;
            }
            $this->logger->error('Failed to fetch line information', ['lineNumber' => $lineNumber, 'error' => $e->getMessage()]);
            throw TramApiException::apiCallFailed('getLines', $e);
        }
    }

    /**
     * Get routes for a specific line
     *
     * @param int $lineNumber
     * @return array
     * @throws TramApiException
     * @throws InvalidArgumentException
     */
    public function getRoutes(int $lineNumber): array
    {
        try {
            if ($lineNumber <= 0) {
                throw TramApiException::invalidLineNumber($lineNumber);
            }

            $this->logger->debug("Fetching line routes", ['lineNumber' => $lineNumber]);

            $response = $this->makeApiRequest('getRoutes', ['line' => $lineNumber]);

            $this->logger->info("Line routes successfully fetched", ['lineNumber' => $lineNumber]);

            return $response;

        } catch (Throwable $e) {
            if ($e instanceof TramApiException) {
                throw $e;
            }
            $this->logger->error('Failed to fetch line routes', ['lineNumber' => $lineNumber, 'error' => $e->getMessage()]);
            throw TramApiException::apiCallFailed('getRoutes', $e);
        }
    }

    /**
     * Get messages for a specific bollard (stop)
     *
     * @param string $bollardSymbol
     * @return array
     * @throws TramApiException
     */
    public function getMessageForBollard(string $bollardSymbol): array
    {
        try {
            $this->logger->debug("Fetching messages for bollard", ['bollardSymbol' => $bollardSymbol]);

            $response = $this->makeApiRequest('findMessagesForBollard', ['symbol' => $bollardSymbol]);

            $this->logger->info("Messages successfully fetched", ['bollardSymbol' => $bollardSymbol]);

            return $response;

        } catch (Throwable $e) {
            if ($e instanceof TramApiException) {
                throw $e;
            }
            $this->logger->error('Failed to fetch messages for bollard', ['bollardSymbol' => $bollardSymbol, 'error' => $e->getMessage()]);
            throw TramApiException::apiCallFailed('findMessagesForBollard', $e);
        }
    }
}
