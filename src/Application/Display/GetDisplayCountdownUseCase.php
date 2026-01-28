<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Application\Countdown\UseCase\GetCurrentCountdownUseCase;
use DateTimeZone;

readonly class GetDisplayCountdownUseCase
{
    public function __construct(
        private GetCurrentCountdownUseCase $getCurrentCountdownUseCase
    ) {}

    public function execute(): ?array
    {
        $currentCountdown = $this->getCurrentCountdownUseCase->execute();

        if (!$currentCountdown) {
            return null;
        }

        $dt = $currentCountdown->countTo->setTimezone(new DateTimeZone('Europe/Warsaw'));

        return [
            'title' => $currentCountdown->title,
            'count_to' => $dt->getTimestamp()
        ];
    }
}
