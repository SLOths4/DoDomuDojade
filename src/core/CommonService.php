<?php
namespace src\core;

use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Dotenv\Dotenv;

class CommonService
{
    protected static ?Logger $logger = null;

    /**
     * @throws Exception
     */
    public static function initLogger(): Logger
    {
        if (self::$logger === null) {
            try {
                self::$logger = new Logger('app');
                $handler = new RotatingFileHandler(__DIR__ . '/../logs/app.log', 7, Level::Debug);
                self::$logger->pushHandler($handler);
            } catch (Exception $e) {
                error_log("Wystąpił błąd podczas inicjalizacji loggera: " . $e->getMessage());
                throw $e;
            }
        }
        return self::$logger;
    }

    /**
     * @param string $variable
     * @return mixed|null
     * @throws Exception
     */
    public static function getConfigVariable(string $variable): mixed
    {
        self::initLogger();
        try {
            $configPath = __DIR__ . '/../config/config.php';

            if (!file_exists($configPath)) {
                self::$logger->error("Plik konfiguracyjny nie istnieje: $configPath");
                return null;
            }

            $config = require $configPath;

            if (!is_array($config)) {
                self::$logger->error("Nieprawidłowy format pliku konfiguracyjnego.");
                return null;
            }

            if (!array_key_exists($variable, $config)) {
                self::$logger->warning("Zmienna konfiguracyjna '$variable' nie została znaleziona.");
                return null;
            }

            return $config[$variable];
        } catch (Exception $e) {
            self::$logger->error("Wystąpił błąd: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Pobiera zmienną z pliku .env
     * @param string $variableName
     * @return string|null
     * @throws Exception
     */
    public static function getEnvVariable(string $variableName): ?string {
        self::initLogger();
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        $value = $_ENV[$variableName] ?? getenv($variableName) ?? null;

        if ($value === null) {
            self::$logger->error("Environment variable $variableName is not set.");
        } else {
            self::$logger->debug("Environment variable $variableName loaded: $value");
        }

        return $value;
    }
}
