<?php

require_once __DIR__ . '/../vendor/autoload.php';

use src\controllers\HomeController;
use src\core\Router;

$router = new Router();

$router->get('/', HomeController::class, 'index');


$router->dispatch();