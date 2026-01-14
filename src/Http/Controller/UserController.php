<?php

namespace App\Http\Controller;

use App\Application\User\CreateUserUseCase;
use App\Application\User\DeleteUserUseCase;
use App\Domain\User\UserException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
use Exception;
use Psr\Log\LoggerInterface;

final class UserController extends BaseController
{
    public function __construct(
        readonly RequestContext $requestContext,
        readonly ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
        private readonly LoggerInterface $logger,
        private readonly CreateUserUseCase $createUserUseCase,
        private readonly DeleteUserUseCase $deleteUserUseCase,
    ) {}

    /**
     * Create a new user
     *
     * @throws UserException
     * @throws Exception
     */
    public function addUser(): void
    {
        $this->logger->debug("Received add user request");
        $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
        $password = trim((string)filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW));

        if (empty($username) || empty($password)) {
            throw UserException::emptyFields();
        }

        $this->createUserUseCase->execute($username, $password);

        $this->flash('success', 'user.created_successfully');
        $this->redirect('/panel/users');
    }

    /**
     * Delete a user
     *
     * @throws UserException
     * @throws Exception
     */
    public function deleteUser(): void
    {
        $this->logger->debug("Received delete user request");
        $userToDeleteId = (int)filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if (!$userToDeleteId || $userToDeleteId <= 0) {
            throw UserException::invalidId();
        }

        $currentUserId = $this->getCurrentUserId();

        $this->deleteUserUseCase->execute($currentUserId, $userToDeleteId);

        $this->flash('success', 'user.deleted_successfully');
        $this->redirect('/panel/users');
    }
}
