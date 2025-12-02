<?php

namespace App\Http\Controller;

class HomeController extends BaseController
{
    public function index(): void
    {
        $this->render('pages/index');
    }
}
