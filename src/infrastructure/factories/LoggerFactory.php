<?php
declare(strict_types=1);

namespace src\infrastructure\factories;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function create(?string $channel = 'app'): LoggerInterface
    {
        $logger = new Logger($channel ?? 'app');
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod');

        if ($env === 'test') {
            $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
            return $logger;
        }

        $logsDir = __DIR__ . '/../../../logs';
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0775, true);
        }
        if (!is_writable($logsDir)) {
            $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
            return $logger;
        }

        $logger->pushHandler(new RotatingFileHandler($logsDir . '/app.log', 7, Level::Debug));
        return $logger;
    }
}