<?php
declare(strict_types=1);

namespace App\Application\User;

use App\Domain\Shared\MissingParameterException;

/**
 * DTO for creating users in UseCase layer
 */
final readonly class CreateUserDTO
{
    public function __construct(
        public string $username,
        public string $password,
    )
    {
    }

    /**
     * Create DTO from HTTP request
     * @throws MissingParameterException
     */
    public static function fromArray(array $array): self
    {
        $username = trim((string)($array['username'] ?? ''));
        $password = trim((string)($array['password'] ?? ''));

        if (empty($username)) {
            throw new MissingParameterException("username");
        }

        if (strlen($username) < 3) {
            throw new MissingParameterException("username must be at least 3 characters long");
        }

        if (strlen($username) > 255) {
            throw new MissingParameterException("username must not exceed 255 characters");
        }

        if (empty($password)) {
            throw new MissingParameterException("password");
        }

        if (strlen($password) < 8) {
            throw new MissingParameterException("password must be at least 8 characters");
        }

        return new self(
            username: $username,
            password: $password,
        );
    }
}