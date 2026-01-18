<?php
declare(strict_types=1);

namespace App\Infrastructure\Redis;

use Predis\Client;
use Predis\Connection\ConnectionException;
use Throwable;

/**
 * Redis factory - creates and manages Predis connections with safety checks
 */
final class RedisFactory
{
    /**
     * Creates Redis client instance with timeouts and reconnection support
     * @param string $host
     * @param int $port
     * @return Client
     * @throws RedisException
     */
    public static function create(
        string $host,
        int $port
    ): Client {
        try {
            $client = new Client([
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'connect_timeout' => 1.0,      // Fail fast on connection
                'read_timeout' => 1.0,         // Prevent hanging reads
                'write_timeout' => 1.0,        // Prevent hanging writes
                'tcp_keepalives' => true,      // Keep connection alive
                'persistent' => false,         // Don't persist in PHP-FPM
                'iterable_multibulk' => true,  // Support large responses
                'throw_errors' => true,        // Throw exceptions, don't return errors
            ]);

            // Verify connection works
            $client->ping();

            return $client;
        } catch (Throwable $e) {
            throw RedisException::creationFailed($e);
        }
    }

    /**
     * Get or create singleton Redis client with auto-reconnect
     * @param string $host
     * @param int $port
     * @return Client
     * @throws RedisException
     */
    public static function createSingleton(
        string $host,
        int $port
    ): Client {
        static $instance = null;

        if ($instance === null) {
            $instance = self::create($host, $port);
            register_shutdown_function([self::class, 'disconnect'], $instance);
            return $instance;
        }

        // Health check - reconnect if dead
        try {
            $instance->ping();
        } catch (ConnectionException|Throwable $e) {
            try {
                $instance->disconnect();
            } catch (Throwable) {
                // Already disconnected, ignore
            }
            $instance = self::create($host, $port);
        }

        return $instance;
    }

    /**
     * Safely disconnect from Redis
     * @param Client $client
     * @return void
     */
    public static function disconnect(Client $client): void
    {
        try {
            $client->disconnect();
        } catch (Throwable) {
            // Already disconnected or errored, ignore
        }
    }
}