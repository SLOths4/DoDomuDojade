<?php

namespace App\Presentation\Http\Controller;

use App\Application\User\CreateUserUseCase;
use App\Application\User\DeleteUserUseCase;
use App\Domain\User\UserException;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class UserController extends BaseController
{
    public function __construct(
         RequestContext                     $requestContext,
         ViewRendererInterface              $renderer,
        private readonly LoggerInterface $logger,
        private readonly Translator $translator,
        private readonly CreateUserUseCase $createUserUseCase,
        private readonly DeleteUserUseCase $deleteUserUseCase,
    ) {
        parent::__construct($requestContext, $renderer);
    }

    public function addUser(): ResponseInterface
    {
        $this->logger->debug("Received add user request");
        $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
        $password = trim((string)filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW));

        if (empty($username) || empty($password)) {
            throw UserException::emptyFields();
        }

        $this->createUserUseCase->execute($username, $password);

        return $this->jsonResponse(201, [
            'success' => true,
            'message' => $this->translator->translate('user.created_successfully'),
        ]);
    }

    public function deleteUser(): ResponseInterface
    {
        $this->logger->debug("Received delete user request");
        $userToDeleteId = (int)filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if (!$userToDeleteId || $userToDeleteId <= 0) {
            throw UserException::invalidId();
        }

        $currentUserId = $this->getCurrentUserId();

        $this->deleteUserUseCase->execute($currentUserId, $userToDeleteId);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('user.deleted_successfully'),
        ]);
    }
}
