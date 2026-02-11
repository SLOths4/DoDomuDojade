<?php
declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\UserException;

/**
 * DTO for changing passwords
 */
final readonly class ChangePasswordDTO
{
    /**
     * @param string $password
     */
    public function __construct(
        public string $password,
    ) {}

    /**
     * Create DTO from an array
     * @param array $array
     * @return self
     * @throws UserException
     */
    public static function fromArray(array $array): self
    {
        $password = trim((string)($array['password'] ?? ''));

        if ($password === '') {
            throw UserException::emptyFields();
        }

        return new self(
            password: $password,
        );
    }
}
