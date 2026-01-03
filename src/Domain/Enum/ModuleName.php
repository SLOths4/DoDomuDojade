<?php
declare(strict_types=1);

namespace App\Domain\Enum;

use App\Domain\Exception\ModuleException;

/**
 * Stores available modules and their names
 */
enum ModuleName: string
{
    case announcement = "announcement";
    case calendar = "calendar";
    case countdown = "countdown";
    case tram = "tram";
    case weather = "weather";
    case quote = "quote";
    case word = "word";

    /**
     * Maps a given string to a module name
     * @param string $value
     * @return ModuleName
     * @throws ModuleException
     */
    public static function fromString(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->name == strtolower($value)){
                return $case;
            }
        }

        throw ModuleException::invalidName($value);
    }
}