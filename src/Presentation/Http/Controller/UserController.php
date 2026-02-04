<?php

namespace App\Presentation\Http\Controller;

use App\Application\User\ChangePasswordDTO;
use App\Application\User\CreateUserDTO;
use App\Application\User\EditUserDTO;
use App\Application\User\UseCase\ChangePasswordUseCase;
use App\Application\User\UseCase\CreateUserUseCase;
use App\Application\User\UseCase\DeleteUserUseCase;
use App\Application\User\UseCase\UpdateUserUseCase;
use App\Domain\Shared\MissingParameterException;
use App\Domain\User\UserException;
use App\Infrastructure\Helper\UserValidationHelper;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class UserController extends BaseController
{
    public function __construct(
         RequestContext                     $requestContext,
         ViewRendererInterface              $renderer,
         private readonly ServerRequestInterface $request,
        private readonly LoggerInterface $logger,
        private readonly Translator $translator,
        private readonly CreateUserUseCase $createUserUseCase,
        private readonly DeleteUserUseCase $deleteUserUseCase,
        private readonly UpdateUserUseCase $updateUserUseCase,
        private readonly ChangePasswordUseCase $changePasswordUseCase,
        private readonly UserValidationHelper $userValidationHelper,
    ) {
        parent::__construct($requestContext, $renderer);
    }

    /**
     * @throws MissingParameterException
     * @throws \Exception
     */
    public function add(): ResponseInterface
    {
        $this->logger->debug("Received create user request");
        $body = json_decode((string)$this->request->getBody(), true);

        $dto = CreateUserDto::fromArray($body);

        $this->createUserUseCase->execute($dto);

        return $this->jsonResponse(201, [
            'success' => true,
            'message' => $this->translator->translate('user.created_successfully'),
        ]);
    }

    /**
     * @throws UserException
     */
    public function delete(array $vars = []): ResponseInterface
    {
        $this->logger->debug("Received delete user request");
        $userToDeleteId = (int)$vars['id'];

        if (!$userToDeleteId || $userToDeleteId <= 0) {
            throw UserException::invalidId();
        }

        $currentUserId = $this->getCurrentUserId();

        $this->deleteUserUseCase->execute($currentUserId, $userToDeleteId);

        return $this->noContentResponse();
    }

    /**
     * @throws UserException
     */
    public function update(array $vars = []): ResponseInterface
    {
        $this->logger->debug("Received update user request");
        $userId = (int)$vars['id'];

        $this->userValidationHelper->validateId($userId);

        $body = json_decode((string)$this->request->getBody(), true);

        if (!is_array($body)) {
            throw UserException::emptyFields();
        }

        $dto = EditUserDTO::fromArray($body);

        $this->updateUserUseCase->execute($userId, $dto);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('user.updated_successfully'),
        ]);
    }

    /**
     * @throws UserException
     */
    public function changePassword(array $vars = []): ResponseInterface
    {
        $this->logger->debug("Received change password request");
        $userId = (int)$vars['id'];

        $this->userValidationHelper->validateId($userId);
        $currentUserId = $this->getCurrentUserId();

        if ($currentUserId !== $userId) {
            throw UserException::unauthorized();
        }

        $body = json_decode((string)$this->request->getBody(), true);

        if (!is_array($body)) {
            throw UserException::emptyFields();
        }

        $dto = ChangePasswordDTO::fromArray($body);

        $this->changePasswordUseCase->execute($userId, $dto->password);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('user.password_changed_successfully'),
        ]);
    }
}
