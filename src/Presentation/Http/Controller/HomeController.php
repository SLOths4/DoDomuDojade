<?php

namespace App\Presentation\Http\Controller;

use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\ViewRendererInterface;
use App\Presentation\View\TemplateNames;
use Psr\Http\Message\ResponseInterface;

final class HomeController extends BaseController
{
    public function __construct(
         RequestContext $requestContext,
         ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
    ){
        parent::__construct($requestContext, $renderer);
    }

    public function index(): ResponseInterface
    {
        return $this->render(TemplateNames::HOME->value);
    }

    public function proposeAnnouncement(): ResponseInterface
    {
        return $this->render(TemplateNames::ANNOUNCEMENT_PROPOSE->value);
    }
}
