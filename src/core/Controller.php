<?php

namespace src\core;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class Controller {
    private static Logger $logger;

    public static function initLogger(): Logger
    {
        if (!isset(self::$logger)){
            try {
                self::$logger = new Logger('controllers');
                self::$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/src.logs', Level::Debug));
            } catch (Exception $e) {
                self::$logger->error("Wystąpił błąd: " . $e->getMessage());
            }
        }
        return self::$logger;
    }

    protected function render($view, $data = []): void
    {
        extract($data);

        include "views/$view.php";
    }
}
