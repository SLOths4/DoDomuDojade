<?php
namespace App\Presentation\Http\Controller;

use App\Domain\User\UserException;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\ViewRendererInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * @property ViewRendererInterface $renderer
 * @property FlashMessengerInterface $flash
 * @property RequestContext $requestContext
 */
abstract class BaseController
{

    public function __construct(
        protected  RequestContext $requestContext,
        protected  ViewRendererInterface $renderer,
    ) {}

    protected function flash(string $key, string $message): void
    {
        $this->flash->flash($key, $message);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $location, int $statusCode = 302): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Location' => $location]
        );
    }

    /**
     * @throws UserException
     */
    protected function getCurrentUserId(): ?int
    {
        $user = $this->requestContext->getCurrentUser();
        if (!$user) {
            throw UserException::unauthorized();
        }
        return $user->id;
    }

    /**
     * Render view and return as response
     */
    protected function render(string $view, array $data = [], int $statusCode = 200): ResponseInterface
    {
        $content = $this->renderer->render($view, $data);

        return new Response(
            $statusCode,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $content
        );
    }

    /**
     * JSON response helper
     */
    protected function jsonResponse(int $statusCode, array $data): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );
    }
}
