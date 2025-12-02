<?php

namespace App\Domain;

use DateTimeImmutable;

class Quote
{
    public function __construct(
        public ?int $id,
        public string $quote,
        public string $author,
        public DateTimeImmutable $fetchedOn
    ){}
}