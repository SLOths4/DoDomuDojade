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
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        set_time_limit(0);
        ignore_user_abort(true);

        $clientId = $_GET['clientId'] ?? uniqid('client_', true);
        $channel = "sse:broadcast";

        echo "data: " . json_encode(['type' => 'connected', 'clientId' => $clientId]) . "\n\n";
        flush();

        try {
            $loop = $this->redis->pubSubLoop();
            $loop->subscribe($channel);

            while (ob_get_level()) ob_end_flush();

            $lastHeartbeat = time();

            foreach ($loop as $msg) {
                if (connection_aborted()) break;

                $now = time();
                if ($now - $lastHeartbeat >= 30) {
                    echo ": " . $now . "\n\n";
                    flush();
                    $lastHeartbeat = $now;
                }

                if ($msg->kind === 'message') {
                    echo "data: $msg->payload\n\n";
                    flush();
                    $lastHeartbeat = time();
                }
            }
        } catch (Throwable $e) {
            error_log("SSE Stream error: " . $e->getMessage());
            exit;
        }
    }

}
