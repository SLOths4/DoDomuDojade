<?php

namespace App\Application\Module;

use App\Domain\Shared\InvalidDateTimeException;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class EditModuleDTO
{
    public function __construct(
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime
    ){}

    /**
     * @throws InvalidDateTimeException
     */
    public static function fromHttpRequest(array $post): self
    {
        $start = $post['start_time'];
        $end = $post['end_time'];

        try {
            $start = new DateTimeImmutable($start);
        } catch (DateMalformedStringException $e) {
            throw new InvalidDateTimeException($start, "start_time", null, $e);
        }

        try {
            $end = new DateTimeImmutable($end);
        } catch (DateMalformedStringException $e) {
            throw new InvalidDateTimeException($end, "end_time", null, $e);
        }

        return new self(
            startTime: $start,
            endTime: $end,
        );
    }
}