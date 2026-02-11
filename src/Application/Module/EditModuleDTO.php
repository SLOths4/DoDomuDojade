<?php

namespace App\Application\Module;

use App\Domain\Shared\InvalidDateTimeException;
use DateMalformedStringException;
use DateTimeImmutable;

/**
 * Edites module
 */
final readonly class EditModuleDTO
{
    /**
     * @param DateTimeImmutable $startTime
     * @param DateTimeImmutable $endTime
     */
    public function __construct(
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime
    ){}

    /**
     * @param array $array
     * @return self
     * @throws InvalidDateTimeException
     */
    public static function fromArray(array $array): self
    {
        $start = $array['start_time'];
        $end = $array['end_time'];

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