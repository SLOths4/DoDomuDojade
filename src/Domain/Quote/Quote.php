<?php

namespace App\Domain\Quote;

use DateTimeImmutable;

/**
 * Quote entity
 */
readonly class Quote
{
    public function __construct(
        public ?int $id,
        public string $quote,
        public string $author,
        public DateTimeImmutable $fetchedOn
    ){}
}