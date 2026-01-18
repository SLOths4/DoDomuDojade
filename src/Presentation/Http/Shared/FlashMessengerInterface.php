<?php

namespace App\Presentation\Http\Shared;

/** Defines FlashMessenger behaviour */
interface FlashMessengerInterface
{
    /**
     * Creates a flash message
     * @param string $key
     * @param string $message
     * @return void
     */
    public function flash(string $key, string $message): void;

    /**
     * Fetches a flash message with a key
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string;

    /**
     * Returns all available messages
     * @return array
     */
    public function getAll(): array;

    /**
     * Clears all available messages
     * @return void
     */
    public function clearAll(): void;
}