<?php

namespace App\Http\Controller;

use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
use Psr\Http\Message\ResponseInterface;

final class HomeController extends BaseController
{
    public function __construct(
        readonly RequestContext $requestContext,
        readonly ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
    ){}

    public function index(): void
    {
        $this->render('pages/index');
    }

    public function proposeAnnouncement(): void
    {
        $this->render('pages/announcementPropose');
    }
}
