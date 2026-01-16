<?php
namespace App\Http\Controller;

use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * @property ViewRendererInterface $renderer
 * @property FlashMessengerInterface $flash
 * @property RequestContext $requestContext
 */
abstract class BaseController
{
    /**
     * Render a view with data
     * Automatically handles error/success messages from the session
     */
    protected function render($view, $data = []): void
    {
        echo $this->renderer->render($view, $data);
    }

    protected function flash(string $key, string $message): void
    {
        $this->flash->flash($key, $message);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $to): void
    {
        header('Location: '. $to);
    }

    protected function getCurrentUserId(): ?int
    {
        $user = $this->requestContext->getCurrentUser();
        return $user->id;
    }
}
