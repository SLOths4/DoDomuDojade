<?php

namespace App\Http\Controller;

use Predis\Client;
use Throwable;

final class SSEStreamController extends BaseController
{
    public function __construct(
        private readonly Client $redis,
    ){}

    public function stream(): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');
        }

        set_time_limit(0);
        ignore_user_abort(false);

        $clientId = $_GET['clientId'] ?? uniqid('client_', true);
        $channel = "sse:broadcast";

        $this->sendEvent('connected', ['clientId' => $clientId]);

        try {
            $loop = $this->redis->pubSubLoop();
            $loop->subscribe($channel);

            $lastHeartbeat = time();

            foreach ($loop as $msg) {
                if (connection_aborted()) break;

                $now = time();
                if ($now - $lastHeartbeat >= 20) {
                    echo ": heartbeat\n\n";
                    if (flush() === false || connection_aborted()) break;
                    $lastHeartbeat = $now;
                }

                if ($msg->kind === 'message') {
                    echo "data: $msg->payload\n\n";
                    if (flush() === false || connection_aborted()) break;
                    $lastHeartbeat = time();
                }
            }
        } catch (Throwable $e) {
            error_log("SSE Stream error: " . $e->getMessage());
            $this->sendEvent('error', ['message' => 'Internal stream error']);
            exit;
        }
    }

    private function sendEvent(string $type, array $data): void
    {
        echo "event: $type\n";
        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }

}
