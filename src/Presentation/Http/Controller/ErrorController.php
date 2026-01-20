<?php

namespace App\Presentation\Http\Controller;

use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\ViewRendererInterface;
use App\Presentation\View\TemplateNames;
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
        return $this->render(TemplateNames::ERROR_404->value);
    }

    public function methodNotAllowed(): ResponseInterface
    {
        return $this->render(TemplateNames::ERROR_405->value);
    }

    public function forbidden(): ResponseInterface
    {
        return $this->render(TemplateNames::ERROR_403->value);
    }

    public function internalServerError(): ResponseInterface
    {
        return $this->render(TemplateNames::ERROR_500->value);
    }
}
