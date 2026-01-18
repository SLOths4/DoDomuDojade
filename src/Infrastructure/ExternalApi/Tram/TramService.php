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
 * Integrates with ZTM (Zarząd Transportu Miejskiego) API for tram data
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
        return $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
    }

    protected function makeApiRequest(string $method, array $params): array
    {
        try {
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

            return $response->toArray();

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('ZTM API transport error', ['method' => $method]);
            throw TramApiException::transportError($e);
        } catch (DecodingExceptionInterface $e) {
            $this->logger->error('ZTM API response decoding error', ['method' => $method]);
            throw TramApiException::decodingError($e);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('ZTM API client error', ['method' => $method]);
            throw TramApiException::clientError($e);
        } catch (ServerExceptionInterface $e) {
            $this->logger->error('ZTM API server error', ['method' => $method]);
            throw TramApiException::serverError($e);
        } catch (RedirectionExceptionInterface $e) {
            $this->logger->error('ZTM API redirection error', ['method' => $method]);
            throw TramApiException::redirectionError($e);
        } catch (Throwable $e) {
            $this->logger->error('ZTM API unexpected error', ['method' => $method, 'error' => $e->getMessage()]);
            throw TramApiException::apiCallFailed($method, $e);
        }
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

            if (!isset($response['success']['times'])) {
                throw TramApiException::noDepartureData($stopId);
            }

            $this->logger->info("Departure times successfully fetched", ['stopId' => $stopId]);

            return $response['success'];

        } catch (Throwable $e) {
            $this->logger->error('Failed to fetch departure times', ['stopId' => $stopId]);
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

        } catch (Throwable $e) {
            $this->logger->error('Failed to fetch nearby stops', ['lat' => $lat, 'lon' => $lon]);
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
            $this->logger->error('Failed to fetch line information', ['lineNumber' => $lineNumber]);
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
            $this->logger->error('Failed to fetch line routes', ['lineNumber' => $lineNumber]);
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
            $this->logger->error('Failed to fetch messages for bollard', ['bollardSymbol' => $bollardSymbol]);
            throw TramApiException::apiCallFailed('findMessagesForBollard', $e);
        }
    }
}
