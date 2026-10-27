<?php
declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Domain\Shared\ValidationException;

/**
 * Password value object
 */
final readonly class Password
{
    private function __construct(
        private string $hash
    ) {}

    /**
     * @param string $plainPassword
     * @param int $minLength
     * @throws ValidationException
     */
    #[\NoDiscard]
    public static function create(
        string $plainPassword,
        int $minLength = 8
    ): self {
        if (mb_strlen($plainPassword) < $minLength) {
            throw ValidationException::invalidInput(['password' => ["Password too short (min $minLength)"]]);
        }
        return new self(password_hash($plainPassword, PASSWORD_DEFAULT));
    }

    /**
     */
    #[\NoDiscard]
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    /**
     * Returns hash of a password
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Verifies plain password against this password hash
     * @param string $plainPassword
     * @return bool match
     */
    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hash);
    }
}
