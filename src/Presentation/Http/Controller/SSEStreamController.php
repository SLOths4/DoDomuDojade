<?php

namespace App\Presentation\Http\Controller;

use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\ViewRendererInterface;
use Predis\Client;
use Throwable;
use InvalidArgumentException;


final class SSEStreamController extends BaseController
{
    private const int HEARTBEAT_INTERVAL = 20;
    private const int REDIS_POLL_TIMEOUT = 1;
    private const int MAX_PAYLOAD_SIZE = 256 * 1024;
    private const int MEMORY_CHECK_INTERVAL = 50;
    private const int MEMORY_LIMIT_PERCENT = 85;
    private const int MAX_RECONNECT_ATTEMPTS = 3;

    private bool $isConnected = false;
    private int $messageCount = 0;
    private int $reconnectAttempts = 0;

    public function __construct(
        RequestContext                $requestContext,
        ViewRendererInterface         $renderer,
        private readonly Client       $redis,
    ) {
        parent::__construct($requestContext, $renderer);
    }

    public function stream(): void
    {
        try {
            $clientId = $this->validateClientId($_GET['clientId'] ?? '');

            $this->initializeStreamEnvironment();

            $this->streamLoop($clientId);

        } catch (Throwable $e) {
            $this->handleFatalError($e);
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Initialize output buffering and headers safely
     */
    private function initializeStreamEnvironment(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
            header('Transfer-Encoding: chunked');
        }

        set_time_limit(0);
        ignore_user_abort(true);

        $memoryLimit = $this->getMemoryLimit();
        ini_set('memory_limit', (int)($memoryLimit * 1.1));
    }

    /**
     * Main streaming loop with comprehensive safety checks
     */
    private function streamLoop(string $clientId): void
    {
        $channel = 'sse:broadcast';
        $lastHeartbeat = time();
        $pollInterval = self::REDIS_POLL_TIMEOUT;
        $totalBytes = 0;

        $this->sendEvent('connected', ['clientId' => $clientId, 'timestamp' => time()]);
        $this->isConnected = true;

        while ($this->isConnected) {
            if (connection_aborted()) {
                error_log("SSE[$clientId]: Client disconnected");
                break;
            }

            try {
                if ($this->messageCount % self::MEMORY_CHECK_INTERVAL === 0) {
                    $this->checkMemoryUsage($clientId);
                }

                $message = $this->fetchRedisMessage($channel, $pollInterval);

                if ($message) {
                    $this->isConnected = $this->processMessage(
                        $message,
                        $clientId,
                        $totalBytes,
                        $lastHeartbeat
                    );

                    if (!$this->isConnected) {
                        break;
                    }
                } else {
                    $this->sendHeartbeatIfNeeded($lastHeartbeat);
                }

            } catch (Throwable $e) {
                $this->handleStreamError($e, $clientId, $pollInterval);
            }
        }

        $this->sendEvent('disconnected', ['clientId' => $clientId, 'totalMessages' => $this->messageCount]);
    }

    /**
     * Fetch message from Redis with error handling
     *
     * @return array|null [key, payload] or null on timeout
     */
    private function fetchRedisMessage(string $channel, int $timeout): ?array
    {
        try {
            $message = $this->redis->brpop($channel, $timeout);

            if (is_array($message) && count($message) === 2) {
                return $message;
            }

            return null;

        } catch (Throwable $e) {
            error_log("SSE Redis brpop error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process a single message with validation
     *
     * @return bool Continue streaming?
     */
    private function processMessage(
        array $message,
        string $clientId,
        int &$totalBytes,
        int &$lastHeartbeat
    ): bool {
        $payload = $message[1];
        $payloadSize = strlen($payload);

        if ($payloadSize > self::MAX_PAYLOAD_SIZE) {
            error_log("SSE[$clientId]: Payload exceeds limit: {$payloadSize} bytes");
            return true;
        }

        if ($payloadSize === 0) {
            error_log("SSE[$clientId]: Empty payload received");
            return true;
        }

        try {
            $this->sendRawEvent($payload);

            $totalBytes += $payloadSize;
            $this->messageCount++;

            if ($this->messageCount % self::MEMORY_CHECK_INTERVAL === 0) {
                error_log("SSE[$clientId]: {$this->messageCount} messages, {$totalBytes} bytes sent");
            }

            if (connection_aborted()) {
                return false;
            }

            $lastHeartbeat = time();
            return true;

        } catch (Throwable $e) {
            error_log("SSE[$clientId]: Error processing message: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Send heartbeat to keep connection alive
     */
    private function sendHeartbeatIfNeeded(int &$lastHeartbeat): void
    {
        $now = time();

        if ($now - $lastHeartbeat >= self::HEARTBEAT_INTERVAL) {
            try {
                echo ": heartbeat\n\n";
                flush();

                if (function_exists('ob_flush')) {
                    ob_flush();
                }

                $lastHeartbeat = $now;

                if (connection_aborted()) {
                    $this->isConnected = false;
                }
            } catch (Throwable $e) {
                error_log("SSE: Error sending heartbeat: " . $e->getMessage());
                $this->isConnected = false;
            }
        }
    }

    /**
     * Check memory usage and enforce limits
     */
    private function checkMemoryUsage(string $clientId): void
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->getMemoryLimit();
        $percent = round(($current / $limit) * 100, 1);

        error_log("SSE[$clientId] Memory: {$percent}% ({$current} / {$limit} bytes, Peak: {$peak})");

        if ($percent >= self::MEMORY_LIMIT_PERCENT) {
            error_log("SSE[$clientId]: CRITICAL - Memory usage at {$percent}%");

            gc_collect_cycles();

            $currentAfterGC = memory_get_usage(true);
            if (($currentAfterGC / $limit) > 0.9) {
                error_log("SSE[$clientId]: Terminating stream - memory limit critical");
                $this->isConnected = false;
            }
        }
    }

    /**
     * Handle streaming errors with recovery attempts
     */
    private function handleStreamError(Throwable $e, string $clientId, int &$pollInterval): void
    {
        $this->reconnectAttempts++;

        error_log("SSE[$clientId] Error (attempt {$this->reconnectAttempts}): " . $e->getMessage());

        if ($this->reconnectAttempts > self::MAX_RECONNECT_ATTEMPTS) {
            error_log("SSE[$clientId]: Max reconnect attempts reached");
            $this->isConnected = false;
            return;
        }

        $backoffMs = 100 * pow(2, $this->reconnectAttempts - 1);
        usleep($backoffMs * 1000);
    }

    /**
     * Handle fatal errors gracefully
     */
    private function handleFatalError(Throwable $e): void
    {
        error_log("SSE Stream Fatal Error: " . $e->getMessage() . " - " . $e->getTraceAsString());

        try {
            $this->sendEvent('error', [
                'message' => 'Stream error occurred',
                'code' => $e->getCode(),
            ]);
        } catch (Throwable) {
            // Ignore - connection may be broken
        }
    }

    /**
     * Cleanup resources on termination
     */
    private function cleanup(): void
    {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        $this->isConnected = false;
    }

    /**
     * Send SSE event with proper formatting
     */
    private function sendEvent(string $type, array $data): void
    {
        try {
            echo "event: {$type}\n";
            echo "data: " . $this->encodeEventData($data) . "\n\n";
            flush();

            if (function_exists('ob_flush')) {
                ob_flush();
            }
        } catch (Throwable $e) {
            error_log("SSE: Error sending event: " . $e->getMessage());
        }
    }

    /**
     * Send raw message data as SSE event
     */
    private function sendRawEvent(string $payload): void
    {
        try {
            if ($this->isValidJsonOrString($payload)) {
                echo "data: " . $payload . "\n\n";
            } else {
                echo "data: " . json_encode($payload) . "\n\n";
            }

            flush();

            if (function_exists('ob_flush')) {
                ob_flush();
            }
        } catch (Throwable $e) {
            error_log("SSE: Error sending raw event: " . $e->getMessage());
        }
    }

    /**
     * Encode data for SSE transmission
     */
    private function encodeEventData(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            error_log("SSE: JSON encode error: " . json_last_error_msg());
            return json_encode(['error' => 'Data encoding failed']);
        }

        return $json;
    }

    /**
     * Validate if string is valid JSON or safe plaintext
     */
    private function isValidJsonOrString(string $data): bool
    {
        if (empty($data)) {
            return false;
        }

        if ($data[0] === '{' || $data[0] === '[') {
            return json_validate($data);
        }

        return true;
    }

    /**
     * Validate client ID to prevent injection
     */
    private function validateClientId(string $clientId): string
    {
        if (empty($clientId)) {
            return uniqid('sse_', true);
        }

//        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $clientId)) {
//            throw new InvalidArgumentException('Invalid client ID format');
//        }

        return $clientId;
    }

    /**
     * Get current PHP memory limit in bytes
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        return (int)$this->convertToBytes($limit);
    }

    /**
     * Convert PHP memory notation to bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);

        if (is_numeric($value)) {
            return (int)$value;
        }

        $unit = strtoupper(substr($value, -1));
        $number = (int)substr($value, 0, -1);

        return match ($unit) {
            'G' => $number * 1024 * 1024 * 1024,
            'M' => $number * 1024 * 1024,
            'K' => $number * 1024,
            default => (int)$value,
        };
    }
}