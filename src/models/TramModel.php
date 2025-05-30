<?php
namespace src\models;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use src\core\Model;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\{ClientExceptionInterface,
    DecodingExceptionInterface,
    RedirectionExceptionInterface,
    ServerExceptionInterface,
    TransportExceptionInterface};
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * PEKA e-monitor API wrapper
 * @author Franciszek Kruszewski <franciszek@kruszew.ski>
 */
class TramModel extends Model
{
    private const array ERROR_MESSAGES = [
        'invalid_response' => 'Invalid or incomplete API response structure',
        'no_departure_data' => 'No departure times available for the specified stop',
        'no_stops_data' => 'No stops found in the specified location',
        'api_error' => 'Error while communicating with ZTM API: %s',
        'invalid_coordinates' => 'Invalid GPS coordinates provided',
        'invalid_line_number' => 'Invalid line number provided',
        'invalid_stop_id' => 'Invalid stop ID format'
    ];

    private HttpClientInterface $httpClient;
    private string $ztmUrl;

    public function __construct(string $ztmUrl) {
        $this->httpClient = HttpClient::create();
        $this->ztmUrl = $ztmUrl;
    }

    /**
     * Validate GPS coordinates.
     * @param float $lat
     * @param float $lon
     * @return bool
     */
    private function isValidCoordinates(float $lat, float $lon): bool {
        return $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
    }

    /**
     * Make API request with common configuration.
     * @param string $method
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function makeApiRequest(string $method, array $params): array {
        try {
            $response = $this->httpClient->request(
                'POST',
                $this->ztmUrl,
                [
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                    'body' => [
                        'method' => $method,
                        'p0' => json_encode($params)
                    ]
                ]
            );

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            self::$logger->error('HTTP transport error', ['method' => $method, 'params' => $params, 'error' => $e->getMessage()]);
            throw new RuntimeException('HTTP transport error occurred while calling API.', 0, $e);
        } catch (DecodingExceptionInterface $e) {
            self::$logger->error('Response decoding error', ['method' => $method, 'response' => $e->getTrace(), 'error' => $e->getMessage()]);
            throw new RuntimeException('Unable to decode API response.', 0, $e);
        } catch (ClientExceptionInterface $e) {
            self::$logger->error('Client error', ['method' => $method, 'params' => $params, 'error' => $e->getMessage()]);
            throw new RuntimeException('Client error occurred while calling API.', 0, $e);
        } catch (ServerExceptionInterface $e) {
            self::$logger->error('Server error', ['method' => $method, 'params' => $params, 'error' => $e->getMessage()]);
            throw new RuntimeException('Server error occurred while calling API.', 0, $e);
        } catch (RedirectionExceptionInterface $e) {
            self::$logger->error('Redirection error', ['method' => $method, 'params' => $params, 'error' => $e->getMessage()]);
            throw new RuntimeException('Redirection error occurred while calling API.', 0, $e);
        } catch (Throwable $e) {
            self::$logger->error('Unexpected error', ['method' => $method, 'params' => $params, 'error' => $e->getMessage()]);
            throw new RuntimeException('An unexpected error occurred while calling API.', 0, $e);
        }
    }

    /**
     * Get departure times for a specific stop.
     * @param string $stopId
     * @return array
     * @throws Exception
     */
    public function getTimes(string $stopId): array {
        if (!preg_match('/^[A-Z0-9]+$/', $stopId)) {
            self::$logger->error(self::ERROR_MESSAGES['invalid_stop_id'], ['stopId' => $stopId]);
            throw new InvalidArgumentException(self::ERROR_MESSAGES['invalid_stop_id']);
        }

        try {
            $response = $this->makeApiRequest('getTimes', ['symbol' => $stopId]);

            if (!isset($response['success']['times'])) {
                self::$logger->error(self::ERROR_MESSAGES['no_departure_data'], ['stopId' => $stopId]);
                throw new Exception(self::ERROR_MESSAGES['no_departure_data']);
            }

            return $response;
        } catch (Exception $e) {
            self::$logger->error('getTimes failed', ['stopId' => $stopId, 'error' => $e->getMessage()]);
            throw new Exception(sprintf(self::ERROR_MESSAGES['api_error'], $e->getMessage()));
        }
    }

    /**
     * Get stops near specified GPS coordinates.
     * @param float $lat
     * @param float $lon
     * @return array
     * @throws Exception
     */
    public function getStops(float $lat, float $lon): array {
        if (!$this->isValidCoordinates($lat, $lon)) {
            self::$logger->error(self::ERROR_MESSAGES['invalid_coordinates'], ['lat' => $lat, 'lon' => $lon]);
            throw new InvalidArgumentException(self::ERROR_MESSAGES['invalid_coordinates']);
        }

        try {
            self::$logger->debug('getStops', ['lat' => $lat, 'lon' => $lon]);
            $response = $this->makeApiRequest('getStops', ['lat' => $lat, 'lon' => $lon]);

            if (empty($response['success'])) {
                self::$logger->error(self::ERROR_MESSAGES['no_stops_data'], ['lat' => $lat, 'lon' => $lon]);
                throw new Exception(self::ERROR_MESSAGES['no_stops_data']);
            }

            return $response['success'];
        } catch (Exception $e) {
            self::$logger->error('getStops failed', ['lat' => $lat, 'lon' => $lon, 'error' => $e->getMessage()]);
            throw new Exception(sprintf(self::ERROR_MESSAGES['api_error'], $e->getMessage()));
        }
    }

    /**
     * Get line information.
     * @param int $lineNumber
     * @return array
     * @throws Exception
     */
    public function getLines(int $lineNumber): array {
        if ($lineNumber <= 0) {
            self::$logger->error(self::ERROR_MESSAGES['invalid_line_number'], ['lineNumber' => $lineNumber]);
            throw new InvalidArgumentException(self::ERROR_MESSAGES['invalid_line_number']);
        }

        try {
            self::$logger->debug('getLines', ['lineNumber' => $lineNumber]);
            return $this->makeApiRequest('getLines', ['line' => $lineNumber]);
        } catch (Exception $e) {
            self::$logger->error('getLines failed', ['lineNumber' => $lineNumber, 'error' => $e->getMessage()]);
            throw new Exception(sprintf(self::ERROR_MESSAGES['api_error'], $e->getMessage()));
        }
    }

    /**
     * Get routes for a specific line.
     * @param int $lineNumber
     * @return array
     * @throws Exception
     */
    public function getRoutes(int $lineNumber): array {
        if ($lineNumber <= 0) {
            self::$logger->error(self::ERROR_MESSAGES['invalid_line_number'], ['lineNumber' => $lineNumber]);
            throw new InvalidArgumentException(self::ERROR_MESSAGES['invalid_line_number']);
        }

        try {
            self::$logger->debug('getRoutes', ['lineNumber' => $lineNumber]);
            return $this->makeApiRequest('getRoutes', ['line' => $lineNumber]);
        } catch (Exception $e) {
            self::$logger->error('getRoutes failed', ['lineNumber' => $lineNumber, 'error' => $e->getMessage()]);
            throw new Exception(sprintf(self::ERROR_MESSAGES['api_error'], $e->getMessage()));
        }
    }
}