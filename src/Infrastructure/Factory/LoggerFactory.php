<?php
declare(strict_types=1);

namespace App\Infrastructure\Factory;

use App\Domain\Exception\LoggerException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Logger factory
 */
final class LoggerFactory
{
    /**
     * Creates LoggerInterface compatible logger instance
     * @param string $logsDirectory
     * @param string $channel
     * @param string $level
     * @return LoggerInterface
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

    /**
     * Checks if the provided log directory is writable
     * @param string $directory
     * @return bool
     */
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

    /**
     * Resolves given log level string into valid level
     * @param string $value
     * @return Level
     */
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
