<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Application\Countdown\UseCase\GetCurrentCountdownUseCase;
use DateTimeZone;
use Exception;

/**
 * Provides countdowns data formatted for the display page
 */
readonly class GetDisplayCountdownUseCase
{
    /**
     * @param GetCurrentCountdownUseCase $getCurrentCountdownUseCase
     */
    public function __construct(
        private GetCurrentCountdownUseCase $getCurrentCountdownUseCase
    ) {}

    /**
     * @return array|null
     * @throws Exception
     */
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
