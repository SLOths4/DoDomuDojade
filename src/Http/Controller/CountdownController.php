<?php

namespace App\Http\Controller;

use App\Domain\Exception\CountdownException;
use App\Http\Context\LocaleContext;
use App\Infrastructure\Translation\LanguageTranslator;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Countdown\CreateCountdownUseCase;
use App\Application\UseCase\Countdown\DeleteCountdownUseCase;
use App\Application\UseCase\Countdown\GetCountdownByIdUseCase;
use App\Application\UseCase\Countdown\UpdateCountdownUseCase;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Service\CsrfTokenService;

class CountdownController extends BaseController
{
    public function __construct(
        AuthenticationService $authenticationService,
        CsrfTokenService $csrfTokenService,
        LoggerInterface $logger,
        LanguageTranslator $translator,
        LocaleContext $localeContext,
        private readonly CreateCountdownUseCase $createCountdownUseCase,
        private readonly DeleteCountdownUseCase $deleteCountdownUseCase,
        private readonly GetCountdownByIdUseCase $getCountdownByIdUseCase,
        private readonly UpdateCountdownUseCase $updateCountdownUseCase,
    ) {
        parent::__construct($authenticationService, $csrfTokenService, $logger, $translator, $localeContext);
    }

    /**
     * Create a new countdown
     *
     * @throws CountdownException
     * @throws Exception
     */
    public function addCountdown(): void
    {
        $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
        $countTo = (string)filter_input(INPUT_POST, 'count_to', FILTER_UNSAFE_RAW);

        if (empty($title) || empty($countTo)) {
            throw CountdownException::emptyFields();
        }

        $userId = $this->getCurrentUserId();
        $this->createCountdownUseCase->execute(['title' => $title, 'count_to' => $countTo], $userId);

        $this->logger->info("Countdown created successfully");
        $this->successAndRedirect('countdown.created_successfully', '/panel/countdowns');
    }

    /**
     * Update an existing countdown
     *
     * @throws CountdownException
     * @throws Exception
     */
    public function editCountdown(): void
    {
        $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

        if (!$countdownId || $countdownId <= 0) {
            throw CountdownException::invalidId();
        }

        $newTitle = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
        $newCountToRaw = (string)filter_input(INPUT_POST, 'count_to', FILTER_UNSAFE_RAW);

        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $newCountToRaw);
        if (!$dt) {
            throw CountdownException::invalidDateFormat();
        }

        $newCountTo = $dt->format('Y-m-d H:i:s');

        $countdown = $this->getCountdownByIdUseCase->execute($countdownId);
        if (!$countdown) {
            throw CountdownException::notFound($countdownId);
        }

        $updates = [];

        if (!empty($newTitle) && $newTitle !== $countdown->title) {
            $updates['title'] = $newTitle;
        }

        if ($newCountTo !== $countdown->countTo->format('Y-m-d H:i:s')) {
            $updates['count_to'] = $newCountTo;
        }

        if (empty($updates)) {
            throw CountdownException::noChanges();
        }

        $this->updateCountdownUseCase->execute($countdownId, $updates);

        $this->logger->info("Countdown updated", ['id' => $countdownId]);
        $this->successAndRedirect('countdown.updated_successfully', '/panel/countdowns');
    }

    /**
     * Delete a countdown
     *
     * @throws CountdownException
     * @throws Exception
     */
    public function deleteCountdown(): void
    {
        $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

        if (!$countdownId || $countdownId <= 0) {
            throw CountdownException::invalidId();
        }

        $this->deleteCountdownUseCase->execute($countdownId);

        $this->logger->info("Countdown deleted", ['id' => $countdownId]);
        $this->successAndRedirect('countdown.deleted_successfully', '/panel/countdowns');
    }
}
