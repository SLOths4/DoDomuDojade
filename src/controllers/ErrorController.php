<?php

namespace src\controllers;

use src\core\Controller;

class ErrorController extends Controller
{
    public function notFound(): void
    {
        $this->render('404');
    }

    public function methodNotAllowed(): void
    {
        $this->render('405');
    }

    public function internalServerError(): void
    {
        $this->render('500');
    }
}
