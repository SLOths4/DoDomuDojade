<?php
namespace App\Infrastructure\Service;

use App\Infrastructure\Helper\SessionHelper;

class FlashMessengerService implements FlashMessengerInterface
{
    private const string PREFIX = 'flash.';

    public function flash(string $key, string $message): void
    {
        SessionHelper::set(sprintf("%s.%s", self::PREFIX, $key), $message);
    }

    public function get(string $key): ?string
    {
        return SessionHelper::get(sprintf("%s.%s", self::PREFIX, $key));
    }

    public function clearAll(): void
    {
        foreach ($_SESSION as $key => $value) {
            if (str_starts_with((string)$key, self::PREFIX)) {
                SessionHelper::remove($key);
            }
        }
    }

    public function all(): array
    {
        return [
            'success' => $this->get('success'),
            'error' => $this->get('error'),
            'info' => $this->get('info'),
        ];
    }
}
