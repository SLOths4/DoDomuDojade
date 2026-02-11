<?php
declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\UserException;

/**
 * DTO for editing users
 */
final readonly class EditUserDTO
{
    /**
     * @param string $username
     * @param string|null $password
     */
    public function __construct(
        public string $username,
        public ?string $password = null,
    ) {}

    /**
     * Create DTO from an array
     * @param array $array
     * @return self
     * @throws UserException
     */
    public static function fromArray(array $array): self
    {
        $username = trim((string)($array['username'] ?? ''));
        $password = $array['password'] ?? null;

        if ($username === '') {
            throw UserException::emptyFields();
        }

        if ($password !== null) {
            $password = trim((string)$password);
            if ($password === '') {
                throw UserException::emptyFields();
            }
        }

        return new self(
            username: $username,
            password: $password,
        );
    }
}
