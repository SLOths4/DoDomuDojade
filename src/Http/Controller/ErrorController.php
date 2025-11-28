<?php

namespace App\Http\Controller;

class ErrorController extends BaseController
{
    public function notFound(): void
    {
        $this->render('errors/404');
    }

    public function methodNotAllowed(): void
    {
        $this->render('errors/405');
    }

    public function internalServerError(): void
    {
        $this->render('errors/500');
    }
}
