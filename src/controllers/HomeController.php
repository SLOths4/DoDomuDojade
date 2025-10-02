<?php

namespace src\controllers;

use src\core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->render('index');
    }
}
