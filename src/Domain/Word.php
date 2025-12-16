<?php

namespace App\Domain;

use DateTimeImmutable;

class Word {
    public function __construct(
        public ?int $id,
        public string $word,
        public string $ipa,
        public string $definition,
        public DateTimeImmutable $fetchedOn
    ){}
}

