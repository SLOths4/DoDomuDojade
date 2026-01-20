<?php
declare(strict_types=1);

namespace App\Infrastructure\Redis;

use Predis\Client;
use Predis\Connection\ConnectionException;
use Throwable;

final class RedisFactory
{
    private static ?Client $instance = null;
    private static int $requestCount = 0;
    private static string $workerId = '';
    private const int MAX_REQUESTS_PER_CONNECTION = 50;

    public static function create(
        string $host,
        int $port
    ): Client {
        try {
            $client = new Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'connect_timeout' => 2.0,
                'read_timeout' => 5.0,
                'write_timeout' => 2.0,
                'tcp_keepalives' => false,
                'persistent' => false,
                'iterable_multibulk' => true,
                'throw_errors' => true,
            ]);

            $client->ping();
            return $client;
        } catch (Throwable $e) {
            throw RedisException::creationFailed($e);
        }
    }

    public static function createSingleton(
        string $host,
        int $port
    ): Client {
        $currentPid = getmypid();
        if (self::$workerId !== (string)$currentPid) {
            self::$workerId = (string)$currentPid;
            self::$requestCount = 0;
            self::disconnect(self::$instance);
            self::$instance = null;
            error_log("[Redis] New PHP-FPM worker detected: PID $currentPid");
        }

        self::$requestCount++;

        if (self::$requestCount > self::MAX_REQUESTS_PER_CONNECTION) {
            error_log("[Redis] Recycling connection after " . self::$requestCount . " requests");
            self::disconnect(self::$instance);
            self::$instance = null;
            self::$requestCount = 0;
        }

        if (self::$instance === null) {
            try {
                self::$instance = self::create($host, $port);
                error_log("[Redis] New connection created (PID: {$currentPid}, Request: " . (self::$requestCount + 1) . ")");
            } catch (Throwable $e) {
                error_log("[Redis CRITICAL] Failed to create connection: " . $e->getMessage());
                throw $e;
            }
            return self::$instance;
        }

        try {
            self::$instance->ping();
        } catch (ConnectionException|Throwable $e) {
            error_log("[Redis] Health check failed, reconnecting: " . $e->getMessage());
            self::disconnect(self::$instance);
            self::$instance = self::create($host, $port);
        }

        return self::$instance;
    }

    public static function disconnect(?Client $client): void
    {
        if ($client === null) {
            return;
        }

        try {
            @$client->disconnect();
            error_log("[Redis] Connection closed");
        } catch (Throwable $e) {
            error_log("[Redis] Disconnect error (ignoring): " . $e->getMessage());
        }
    }

    public static function getStats(): array
    {
        return [
            'pid' => getmypid(),
            'worker_id' => self::$workerId,
            'request_count' => self::$requestCount,
            'max_requests' => self::MAX_REQUESTS_PER_CONNECTION,
            'has_instance' => self::$instance !== null,
        ];
    }
}
