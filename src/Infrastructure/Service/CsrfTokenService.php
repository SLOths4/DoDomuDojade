<?php

namespace App\Infrastructure\Service;

use App\Infrastructure\Helper\SessionHelper;
use Random\RandomException;

/**
 * Helps with managing csrf token
 */
readonly class CsrfTokenService
{
    /** @var string csrf token key in arrays and HTML forms */
    private const string TOKEN_KEY = 'csrf_token';
    /** @var int used token lenght */
    private const int TOKEN_LENGTH = 32;

    /**
     * Returns csrf token from session
     *
     * @return string
     */
    private function get(): string
    {
        return SessionHelper::get(self::TOKEN_KEY);
    }

    /**
     * Sets CSRF token if the session doesn't have one
     *
     * @return string session token
     * @throws RandomException
     */
    public function getOrCreate(): string
    {
        $hasCsrf = SessionHelper::has(self::TOKEN_KEY);
        if (!$hasCsrf) {
            SessionHelper::set(self::TOKEN_KEY, $this->generate());
        }
        return $this->get();
    }

    /**
     * Compares provided token against session token
     *
     * @param string $providedToken
     * @return bool
     */
    public function validate(string $providedToken): bool
    {
        $sessionToken = $this->get();

        if (!$sessionToken) {
            return false;
        }

        return hash_equals($sessionToken, $providedToken);
    }

    /**
     * Generates token with set length
     * @return string
     * @throws RandomException
     */
    public function generate(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }
}