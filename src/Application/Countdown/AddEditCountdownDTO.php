<?php
declare(strict_types=1);

namespace App\Application\Countdown;

use App\Domain\Shared\InvalidDateTimeException;
use App\Domain\Shared\MissingParameterException;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class AddEditCountdownDTO
{
    public function __construct(
        public string            $title,
        public DateTimeImmutable $countTo,
    ){}

    /**
     * @throws InvalidDateTimeException
     * @throws MissingParameterException
     */
    public static function fromArray(array $array): self
    {
        $title = (string)($array['title']);
        $countTo = $array['count_to'];

        try {
            $countTo = new DateTimeImmutable($countTo);
        } catch (DateMalformedStringException $e) {
            throw new InvalidDateTimeException($countTo, "count_to", null, $e);
        }

        if (empty($title)) {
            throw new MissingParameterException("title");
        }

        return new self(
            title: $title,
            countTo: $countTo,
        );
    }
}
