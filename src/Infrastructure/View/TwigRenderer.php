<?php

namespace App\Infrastructure\View;

use App\Http\Context\LocaleContext;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerService;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class TwigRenderer implements ViewRendererInterface
{
    public function __construct(
     private Environment    $twig,
     private RequestContext $requestContext,
     private LocaleContext $localeContext,
     private FlashMessengerService $flashMessengerService,
    ){}

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(string $template, array $data = []): string
    {
        $data = array_merge($this->getGlobals(), $data);
        if (!str_ends_with($template, '.twig')) {
            $template .= '.twig';
        }

        return $this->twig->render($template, $data);
    }

    private function getGlobals(): array
    {
        $token = $this->requestContext->get('csrf_token') ?? '';
        error_log("RequestContext csrf_token: " . $token);

        $flash = $this->flashMessengerService->all();
        $this->flashMessengerService->clearAll();

        return [
            'user' => $this->requestContext->get('user'),
            'flash' => $flash,
            'locale' => $this->localeContext->get(),
            'config' => [
                'app_name' => 'DoDomuDojade',
                'version' => '1.0'
            ],
            'csrf' => [
                'token' => $token,
            ],
        ];
    }
}