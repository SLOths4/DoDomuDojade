<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\Shared\AuthenticationException;

final readonly class AuthenticateUserDTO
{
    public function __construct(
        public string $username,
        public string $password
    ) {}

    public static function fromArray(array $data): self
    {
        $username = trim((string)($data['username'] ?? ''));
        $password = trim((string)($data['password'] ?? ''));

        return new self($username, $password);
    }

    /**
     * @throws AuthenticationException
     */
    public function validate(): void
    {
        if ($this->username === '' || $this->password === '') {
            throw AuthenticationException::emptyCredentials();
        }
    }
}
