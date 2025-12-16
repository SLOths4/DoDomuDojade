<?php
declare(strict_types=1);

namespace App\Infrastructure\Factory;

use App\Infrastructure\Exception\LoggerException;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Psr\Log\LoggerInterface;
use Throwable;

final class LoggerFactory
{
    /**
     * @throws LoggerException
     */
    public static function create(
        string $logsDirectory,
        string $channel,
        string $level = 'info'
    ): LoggerInterface {
        try {
            $logger = new Logger($channel);
            $resolvedLevel = self::resolveLogLevel($level);

            if (!self::ensureLogsDirectory($logsDirectory)) {
                $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
                return $logger;
            }

            if ($resolvedLevel === Level::Debug) {
                $logger->pushHandler(
                    new RotatingFileHandler(
                        "$logsDirectory/app.log",
                        14,
                        Level::Debug
                    )
                );
                return $logger;
            }

            $logger->pushHandler(
                new RotatingFileHandler(
                    "$logsDirectory/app.log",
                    7,
                    Level::Info
                )
            );

            return $logger;
        } catch (Throwable $e) {
            throw LoggerException::creationFailed($e);
        }
    }

    private static function ensureLogsDirectory(string $directory): bool
    {
        try {
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
            return is_writable($directory);
        } catch (Throwable) {
            return false;
        }
    }

    private static function resolveLogLevel(string $value): Level
    {
        return match (strtolower(trim($value))) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Info,
        };
    }
}
