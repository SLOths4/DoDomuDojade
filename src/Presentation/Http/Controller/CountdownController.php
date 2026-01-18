<?php

namespace App\Presentation\Http\Controller;

use App\Application\Countdown\AddEditCountdownDTO;
use App\Application\Countdown\UseCase\CreateCountdownUseCase;
use App\Application\Countdown\UseCase\DeleteCountdownUseCase;
use App\Application\Countdown\UseCase\UpdateCountdownUseCase;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class CountdownController extends BaseController
{
    public function __construct(
        RequestContext $requestContext,
        ViewRendererInterface $renderer,
        private readonly LoggerInterface $logger,
        private readonly Translator $translator,
        private readonly CreateCountdownUseCase $createCountdownUseCase,
        private readonly DeleteCountdownUseCase $deleteCountdownUseCase,
        private readonly UpdateCountdownUseCase $updateCountdownUseCase,
    ) {
        parent::__construct($requestContext, $renderer);
    }

    public function addCountdown(): ResponseInterface
    {
        $this->logger->debug("Received add countdown request");
        $dto = AddEditCountdownDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->createCountdownUseCase->execute($dto, $userId);

        return $this->jsonResponse(201, [
            'success' => true,
            'message' => $this->translator->translate('countdown.created_successfully'),
        ]);
    }

    public function editCountdown(): ResponseInterface
    {
        $this->logger->debug("Received edit countdown request");
        $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

        $dto = AddEditCountdownDTO::fromHttpRequest($_POST);
        $userId = $this->getCurrentUserId();

        $this->updateCountdownUseCase->execute($countdownId, $dto, $userId);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('countdown.updated_successfully'),
        ]);
    }

    public function deleteCountdown(): ResponseInterface
    {
        $this->logger->debug("Received delete countdown request");
        $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

        $this->deleteCountdownUseCase->execute($countdownId);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('countdown.deleted_successfully'),
        ]);
    }

    protected function jsonResponse(int $statusCode, array $data): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );
    }
}
