<?php

namespace App\Domain\Weather;

use DateTimeImmutable;

readonly class WeatherSnapshot
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public array $payload,
        public DateTimeImmutable $fetchedOn,
        public ?int $id = null,
    ) {}
}
