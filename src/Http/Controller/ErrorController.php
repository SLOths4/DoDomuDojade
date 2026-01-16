<?php

namespace App\Http\Controller;

use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;

final class ErrorController extends BaseController
{
    public function __construct(
        readonly RequestContext $requestContext,
        readonly ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
    ){}

    public function notFound(): string
    {
        return $this->render('errors/404');
    }

    public function methodNotAllowed(): string
    {
        return $this->render('errors/405');
    }

    public function forbidden(): string
    {
        return $this->render('errors/403');
    }

    public function internalServerError(): string
    {
        return $this->render('errors/500');
    }
}
