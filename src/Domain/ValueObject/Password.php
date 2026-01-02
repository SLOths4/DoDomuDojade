<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\ValidationException;

/**
 * Password value object
 */
final readonly class Password
{
    private string $hash;

    /**
     * @param string $plainPassword
     * @param int $minLength
     * @throws ValidationException
     */
    public function __construct(
        string $plainPassword,
        int $minLength = 8
    ) {
        if (mb_strlen($plainPassword) < $minLength) {
            throw ValidationException::invalidInput(['password' => ["Password too short (min $minLength)"]]);
        }
        $this->hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    /**
     * @throws ValidationException
     */
    public static function fromHash(string $hash): self
    {
        $instance = new self('dummy_pass', 0);
        $instance->hash = $hash;
        return $instance;
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
