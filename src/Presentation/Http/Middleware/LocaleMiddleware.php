<?php
namespace App\Presentation\Http\Middleware;

use App\Presentation\Http\Context\LocaleContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LocaleMiddleware implements MiddlewareInterface
{
    private const array ALLOWED_LOCALES = ['en', 'pl'];
    private const string DEFAULT_LOCALE = 'en';

    public function __construct(
        private LocaleContext $context
    ) {}

    public function handle(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $locale = $this->detectLocale();
        $this->context->set($locale);
        return $next($request);
    }

    private function detectLocale(): string
    {
        if (isset($_GET['lang'])) {
            return $this->validate($_GET['lang']);
        }

        $sessionLocale = $this->getSessionLocale();
        if ($sessionLocale) {
            return $sessionLocale;
        }

        $headerLocale = $this->parseAcceptLanguageHeader();
        if ($headerLocale) {
            return $headerLocale;
        }

        return self::DEFAULT_LOCALE;
    }

    private function getSessionLocale(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $locale = $_SESSION['locale'] ?? null;
        return $locale ? $this->validate($locale) : null;
    }

    private function parseAcceptLanguageHeader(): ?string
    {
        $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if (!$header) {
            return null;
        }

        $languages = [];
        foreach (explode(',', $header) as $lang) {
            $parts = explode(';', trim($lang));
            $code = trim($parts[0]);

            $locale = substr($code, 0, 2);

            $quality = 1.0;
            if (isset($parts[1])) {
                preg_match('/q=([0-9.]+)/', $parts[1], $matches);
                $quality = $matches[1] ?? 1.0;
            }

            if ($locale) {
                $languages[] = [
                    'locale' => strtolower($locale),
                    'quality' => (float)$quality,
                ];
            }
        }

        usort($languages, fn($a, $b) => $b['quality'] <=> $a['quality']);

        foreach ($languages as $lang) {
            $validated = $this->validate($lang['locale']);
            if ($validated) {
                return $validated;
            }
        }

        return null;
    }

    private function validate(string $locale): ?string
    {
        $locale = strtolower(trim($locale));
        return in_array($locale, self::ALLOWED_LOCALES, true) ? $locale : null;
    }
}
