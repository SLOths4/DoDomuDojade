<?php

namespace App\Infrastructure\Service;
interface FlashMessengerInterface
{
    public function flash(string $key, string $message): void;
    public function get(string $key): ?string;
    public function getAll(): array;
    public function clearAll(): void;
}