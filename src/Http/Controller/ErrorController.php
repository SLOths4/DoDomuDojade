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
