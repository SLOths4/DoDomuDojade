<?php

namespace App\Presentation\Http\Controller;

use App\Application\Countdown\AddEditCountdownDTO;
use App\Application\Countdown\UseCase\CreateCountdownUseCase;
use App\Application\Countdown\UseCase\DeleteCountdownUseCase;
use App\Application\Countdown\UseCase\GetAllCountdownsUseCase;
use App\Application\Countdown\UseCase\UpdateCountdownUseCase;
use App\Application\User\UseCase\GetAllUsersUseCase;
use App\Domain\User\UserException;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use DateTimeImmutable;
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
        private readonly GetAllCountdownsUseCase $getAllCountdownsUseCase,
        private readonly GetAllUsersUseCase $getAllUsersUseCase,
    ) {
        parent::__construct($requestContext, $renderer);
    }

    /**
     * Build map of user IDs to usernames for display purposes.
     */
    private function buildUsernamesMap(array $users): array
    {
        $usernames = [];
        foreach ($users as $user) {
            $usernames[$user->id] = $user->username;
        }

        return $usernames;
    }

    /**
     * Format countdown objects for API response.
     */
    private function formatCountdowns(array $countdowns, array $usernames): array
    {
        return array_map(
            static fn ($countdown) => [
                'id' => $countdown->id,
                'title' => $countdown->title,
                'countTo' => $countdown->countTo instanceof DateTimeImmutable
                    ? $countdown->countTo->format('Y-m-d H:i')
                    : (string) $countdown->countTo,
                'countToEdit' => $countdown->countTo instanceof DateTimeImmutable
                    ? $countdown->countTo->format('Y-m-d\TH:i')
                    : (string) $countdown->countTo,
                'authorName' => $usernames[$countdown->userId] ?? 'Nieznany użytkownik',
            ],
            $countdowns,
        );
    }

    /**
     * Get all countdowns via API.
     * GET /api/countdowns
     * @throws \Exception
     */
    public function getAll(): ResponseInterface
    {
        $users = $this->getAllUsersUseCase->execute();
        $countdowns = $this->getAllCountdownsUseCase->execute();

        return $this->jsonResponse(200, $this->formatCountdowns(
            $countdowns,
            $this->buildUsernamesMap($users),
        ));
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
