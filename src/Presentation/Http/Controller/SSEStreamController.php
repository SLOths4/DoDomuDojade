<?php

namespace App\Presentation\Http\Controller;

use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\ViewRendererInterface;
use Predis\Client;
use Throwable;

final class SSEStreamController extends BaseController
{
    public function __construct(
        RequestContext                     $requestContext,
        ViewRendererInterface              $renderer,
        private readonly Client $redis,
    ){
        parent::__construct($requestContext, $renderer);
    }

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
        ignore_user_abort(true);

        $clientId = $_GET['clientId'];
        $channel = "sse:broadcast";

        $this->sendEvent('connected', ['clientId' => $clientId]);

        try {
            $lastHeartbeat = time();
            $pollInterval = 100000;

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                try {
                    $message = $this->redis->brpop($channel, 1);

                    if ($message) {
                        $payload = $message[1];
                        echo "data: " . $payload . "\n\n";
                        if (connection_aborted()) {
                            break;
                        }
                        $lastHeartbeat = time();
                    }

                } catch (Throwable $e) {
                    error_log("SSE Redis error: " . $e->getMessage());
                    usleep($pollInterval);
                    continue;
                }

                $now = time();
                if ($now - $lastHeartbeat >= 20) {
                    echo ": heartbeat\n\n";
                    if (connection_aborted()) {
                        break;
                    }
                    $lastHeartbeat = $now;
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
