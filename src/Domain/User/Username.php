<?php
declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Exception\ValidationException;

final readonly class Username
{
    /**
     * @throws ValidationException
     */
    public function __construct(
        public string $value,
        int $maxLength = 255
    ) {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw ValidationException::invalidInput(['username' => ['Username cannot be empty']]);
        }
        if (mb_strlen($trimmed) > $maxLength) {
            throw ValidationException::invalidInput(['username' => ["Username too long (max $maxLength)"]]);
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
