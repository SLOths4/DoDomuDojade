<?php

namespace App\Infrastructure\Twig;

use App\Infrastructure\Service\FlashMessengerService;
use App\Presentation\Http\Context\LocaleContext;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class TwigRenderer implements ViewRendererInterface
{
    public function __construct(
        private Environment $twig,
        private RequestContext $requestContext,
        private LocaleContext $localeContext,
        private FlashMessengerService $flashMessengerService,
        private Translator $translator,
    ) {}

    /**
     * @throws TwigException
     */
    public function render(string $template, array $data = []): string
    {
        try {
            $data = array_merge($this->getGlobals(), $data);

            if (!str_ends_with($template, '.twig')) {
                $template .= '.twig';
            }

            return $this->twig->render($template, $data);

        } catch (LoaderError) {
            throw TwigException::templateNotFound($template);
        } catch (RuntimeError | SyntaxError|Throwable $e) {
            throw TwigException::renderingFailed($template, $e);
        }
    }

    private function getGlobals(): array
    {
        $token = $this->requestContext->get('csrf_token');
        $flash = $this->flashMessengerService->getAll();

        if ($flash['error']) {
            $flash['error'] = $this->translator->translate($flash['error']);
        }
        if ($flash['success']) {
            $flash['success'] = $this->translator->translate($flash['success']);
        }

        $this->flashMessengerService->clearAll();

        return [
            'user' => $this->requestContext->get('user'),
            'flash' => $flash,
            'locale' => $this->localeContext->get(),
            'config' => [
                'app_name' => 'DoDomuDojadę',
                'version' => '1.0',
            ],
            'csrf' => [
                'token' => $token,
            ],
        ];
    }
}
