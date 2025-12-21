<?php
declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Infrastructure\Exception\ValidationException;

final readonly class Password
{
    private string $hash;

    /**
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
        $instance = new self('dummy_pass', 0); // Bypass validation
        $instance->hash = $hash;
        return $instance;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hash);
    }
}
