<?php

namespace App\Domain\Exception;

use App\Domain\Shared\DomainException;
use Throwable;

class ConfigException extends DomainException
{
    private const int CODE_MISSING_VARIABLE = 100;
    private const int CODE_LOADING_FAILED = 101;
    private function __construct(string $message, int $code = 0, array $context = [], ?Throwable $previous = null) {
        parent::__construct($message, $code, $context, $previous);
    }

    public static function loadingFailed(?Throwable $previous = null): self {
        return new self(
            "Error while loading configuration",
            self::CODE_LOADING_FAILED,
            [],
            $previous
        );
    }

    public static function missingVariable(string $variableName, ?Throwable $previous = null): ConfigException{
        return new self (
            "Variable is not set",
            self::CODE_MISSING_VARIABLE,
            [
                'variableName' => $variableName
            ],
            $previous
        );
    }
}