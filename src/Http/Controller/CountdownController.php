<?php

namespace App\Http\Controller;

use App\Application\DataTransferObject\AddEditCountdownDTO;
use App\Domain\Exception\CountdownException;
use App\Http\Context\LocaleContext;
use App\Infrastructure\Translation\LanguageTranslator;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Countdown\CreateCountdownUseCase;
use App\Application\UseCase\Countdown\DeleteCountdownUseCase;
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
        private readonly UpdateCountdownUseCase $updateCountdownUseCase,
    ) {
        parent::__construct($authenticationService, $csrfTokenService, $logger, $translator, $localeContext);
    }

    /**
     * Create a new countdown
     * @throws Exception
     */
    #[NoReturn]
    public function addCountdown(): void
    {
        $dto = AddEditCountdownDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->createCountdownUseCase->execute($dto, $userId);

        $this->successAndRedirect('countdown.created_successfully', '/panel/countdowns');
    }

    /**
     * Updates an existing countdown
     * @throws Exception
     */
    #[NoReturn]
    public function editCountdown(): void
    {
        $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

        $dto = AddEditCountdownDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->updateCountdownUseCase->execute($countdownId, $dto, $userId);

        $this->successAndRedirect('countdown.updated_successfully', '/panel/countdowns');
    }

    /**
     * Deletes a countdown
     * @throws CountdownException
     * @throws Exception
     */
    #[NoReturn]
    public function deleteCountdown(): void
    {
        $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

        $this->deleteCountdownUseCase->execute($countdownId);

        $this->successAndRedirect('countdown.deleted_successfully', '/panel/countdowns');
    }
}
