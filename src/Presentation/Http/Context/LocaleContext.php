<?php
namespace App\Presentation\Http\Context;

final class LocaleContext
{
    private string $locale = 'en';

    public function set(string $locale): void
    {
        $this->locale = $locale;
    }

    public function get(): string
    {
        return $this->locale;
    }
}
