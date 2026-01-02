<?php
namespace App\Infrastructure\Service;

use App\Infrastructure\Helper\SessionHelper;

/**
 * Helps with managing flash messages
 */
class FlashMessengerService implements FlashMessengerInterface
{
    private const string PREFIX = 'flash';

    public function flash(string $key, string $message): void
    {
        SessionHelper::set(sprintf("%s.%s", self::PREFIX, $key), $message);
    }

    public function get(string $key): ?string
    {
        return SessionHelper::get(sprintf("%s.%s", self::PREFIX, $key));
    }

    /**
     * Clears all messages
     * @return void
     */
    public function clearAll(): void
    {
        foreach ($_SESSION as $key => $value) {
            if (str_starts_with((string)$key, self::PREFIX)) {
                SessionHelper::remove($key);
            }
        }
    }

    /**
     * Returns all flash messages
     * @return array{
     *      success?: string,
     *      error?: string,
     *  }
     */
    public function getAll(): array
    {
        return [
            'success' => $this->get('success'),
            'error' => $this->get('error'),
        ];
    }
}
