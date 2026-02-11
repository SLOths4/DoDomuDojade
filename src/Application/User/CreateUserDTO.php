<?php
declare(strict_types=1);

namespace App\Application\User;

use App\Domain\Shared\MissingParameterException;

/**
 * DTO for creating users
 */
final readonly class CreateUserDTO
{
    /**
     * @param string $username
     * @param string $password
     */
    public function __construct(
        public string $username,
        public string $password,
    ) {}

    /**
     * Create DTO from an array
     * @param array $array
     * @return self
     * @throws MissingParameterException
     */
    public static function fromArray(array $array): self
    {
        $username = trim((string)($array['username'] ?? ''));
        $password = trim((string)($array['password'] ?? ''));

        if (empty($username)) {
            throw new MissingParameterException("username");
        }

        if (empty($password)) {
            throw new MissingParameterException("password");
        }

        return new self(
            username: $username,
            password: $password,
        );
    }
}