<?php
namespace App\Infrastructure\Translation;

use App\Presentation\Http\Context\LocaleContext;
use App\Presentation\Http\Shared\Translator;

final class LanguageTranslator implements Translator
{
    private array $cache;
    private string $translationsPath;
    private string $defaultLocale;

    public function __construct(
        private readonly LocaleContext $context,
        string $translationsPath = __DIR__ . '/resources/'
    ) {
        $this->translationsPath = rtrim($translationsPath, '/');
        $this->cache = [];
        $this->defaultLocale = 'en';
    }

    /**
     * Translate a key with optional parameters
     *
     * Examples:
     * - trans('auth.invalid_credentials')
     * - trans('announcement.not_found')
     * - trans('errors.database.connection', ['host' => 'localhost'])
     *
     * @param string $key Dot-notation key (e.g., 'auth.invalid_credentials')
     * @param array $params Parameters to interpolate in the message
     * @return string Translated message
     */
    public function translate(string $key, array $params = []): string
    {
        $locale = $this->context->get();

        $messages = $this->loadMessages($locale);

        $message = $this->getNestedValue($messages, $key);

        if ($message === null && $locale !== $this->defaultLocale) {
            $defaultMessages = $this->loadMessages($this->defaultLocale);
            $message = $this->getNestedValue($defaultMessages, $key);
        }

        $message = $message ?? $key;

        foreach ($params as $placeholder => $value) {
            $message = str_replace(":$placeholder", (string)$value, $message);
        }

        return $message;
    }

    /**
     * Get nested array value using dot notation
     *
     * Examples:
     * - getNestedValue(['auth' => ['invalid' => 'msg']], 'auth.invalid') → 'msg'
     * - getNestedValue(['auth' => [...]], 'auth.missing') → null
     *
     * @param array $array Array to search
     * @param string $key Dot-notation key (e.g., 'auth.invalid_credentials')
     * @return string|null
     */
    private function getNestedValue(array $array, string $key): ?string
    {
        $keys = explode('.', $key);

        $current = $array;
        foreach ($keys as $k) {
            if (!is_array($current) || !isset($current[$k])) {
                return null;
            }
            $current = $current[$k];
        }

        return is_string($current) ? $current : null;
    }

    /**
     * Load messages for a specific locale
     * Caches result to avoid re-reading files
     *
     * @param string $locale Locale code (e.g., 'en', 'pl')
     * @return array Translation array
     */
    private function loadMessages(string $locale): array
    {
        if (isset($this->cache[$locale])) {
            return $this->cache[$locale];
        }

        $path = "$this->translationsPath/$locale.php";

        if (!file_exists($path)) {
            return [];
        }

        return $this->cache[$locale] = require $path;
    }
}
