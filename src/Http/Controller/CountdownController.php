<?php

namespace App\Http\Controller;

use App\Application\DataTransferObject\AddEditCountdownDTO;
use App\Domain\Exception\CountdownException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Countdown\CreateCountdownUseCase;
use App\Application\UseCase\Countdown\DeleteCountdownUseCase;
use App\Application\UseCase\Countdown\UpdateCountdownUseCase;

final class CountdownController extends BaseController
{
    public function __construct(
        private readonly LoggerInterface                         $logger,
        readonly ViewRendererInterface                   $renderer,
        readonly FlashMessengerInterface                 $flash,
        readonly RequestContext                          $requestContext,
        private readonly CreateCountdownUseCase $createCountdownUseCase,
        private readonly DeleteCountdownUseCase $deleteCountdownUseCase,
        private readonly UpdateCountdownUseCase $updateCountdownUseCase,
    ) {}

    /**
     * Create a new countdown
     * @throws Exception
     */
    #[NoReturn]
    public function addCountdown(): void
    {
        $this->logger->debug("Received add countdown request");
        $dto = AddEditCountdownDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->createCountdownUseCase->execute($dto, $userId);

        $this->flash('success', 'countdown.created_successfully');
        $this->redirect('/panel/countdowns');
    }

    /**
     * Updates an existing countdown
     * @throws Exception
     */
    #[NoReturn]
    public function editCountdown(): void
    {
        $this->logger->debug("Received edit countdown request");
        $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

        $dto = AddEditCountdownDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->updateCountdownUseCase->execute($countdownId, $dto, $userId);

        $this->flash('success', 'countdown.updated_successfully');
        $this->redirect('/panel/countdowns');
    }

    /**
     * Deletes a countdown
     * @throws CountdownException
     * @throws Exception
     */
    #[NoReturn]
    public function deleteCountdown(): void
    {
        $this->logger->debug("Received delete countdown request");
        $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

        $this->deleteCountdownUseCase->execute($countdownId);

        $this->flash('success', 'countdown.deleted_successfully');
        $this->redirect('/panel/countdowns');
    }
}
