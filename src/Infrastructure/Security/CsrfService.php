<?php

namespace App\Infrastructure\Security;

use Random\RandomException;

class CsrfService
{
    /**
     * @throws RandomException
     */
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function validateCsrf(string $token, string $sessionToken): bool
    {
        return hash_equals($token, $sessionToken);
    }
}