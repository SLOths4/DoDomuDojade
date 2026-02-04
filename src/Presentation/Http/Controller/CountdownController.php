<?php

namespace App\Presentation\Http\Controller;

use App\Application\Countdown\AddEditCountdownDTO;
use App\Application\Countdown\UseCase\CreateCountdownUseCase;
use App\Application\Countdown\UseCase\DeleteCountdownUseCase;
use App\Application\Countdown\UseCase\UpdateCountdownUseCase;
use App\Domain\User\UserException;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class CountdownController extends BaseController
{
    public function __construct(
        RequestContext $requestContext,
        ViewRendererInterface $renderer,
        private readonly ServerRequestInterface $request,
        private readonly LoggerInterface $logger,
        private readonly Translator $translator,
        private readonly CreateCountdownUseCase $createCountdownUseCase,
        private readonly DeleteCountdownUseCase $deleteCountdownUseCase,
        private readonly UpdateCountdownUseCase $updateCountdownUseCase,
    ) {
        parent::__construct($requestContext, $renderer);
    }

    /**
     * Create countdown via API
     * POST /api/countdown
     * @throws UserException
     * @throws \Exception
     */
    public function add(): ResponseInterface
    {
        $this->logger->debug("Received create countdown request");
        $body = json_decode((string)$this->request->getBody(), true);
        $userId = $this->getCurrentUserId();

        $dto = AddEditCountdownDTO::fromArray($body);

        $this->createCountdownUseCase->execute($dto, $userId);

        return $this->jsonResponse(201, [
            'success' => true,
            'message' => $this->translator->translate('countdown.created_successfully'),
        ]);
    }

    /**
     * Update countdown via API
     * PATCH /api/countdown/{id}
     * @throws UserException
     * @throws \Exception
     */
    public function update(array $vars = []): ResponseInterface
    {
        $this->logger->debug("Received update countdown request");
        $countdownId = (int)$vars['id'];
        $body = json_decode((string)$this->request->getBody(), true);
        $userId = $this->getCurrentUserId();

        $dto = AddEditCountdownDTO::fromArray($body);

        $this->updateCountdownUseCase->execute($countdownId, $dto, $userId);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('countdown.updated_successfully'),
        ]);
    }

    /**
     * Delete countdown via API
     * DELETE /api/countdown/{id}
     */
    public function delete(array $vars = []): ResponseInterface
    {
        $this->logger->debug("Received delete countdown request");
        $countdownId = (int)$vars['id'];

        $this->deleteCountdownUseCase->execute($countdownId);

        return $this->noContentResponse();
    }
}
