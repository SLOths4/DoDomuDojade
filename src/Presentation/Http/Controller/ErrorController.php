<?php

namespace App\Presentation\Http\Controller;

use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\ViewRendererInterface;
use Psr\Http\Message\ResponseInterface;

final class ErrorController extends BaseController
{
    public function __construct(
         RequestContext $requestContext,
         ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
    ){
        parent::__construct($requestContext, $renderer);
    }

    public function notFound(): ResponseInterface
    {
        return $this->render('errors/404');
    }

    public function methodNotAllowed(): ResponseInterface
    {
        return $this->render('errors/405');
    }

    public function forbidden(): ResponseInterface
    {
        return $this->render('errors/403');
    }

    public function internalServerError(): ResponseInterface
    {
        return $this->render('errors/500');
    }
}
