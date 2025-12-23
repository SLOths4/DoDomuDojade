<?php

namespace App\Http\Controller;

use App\Domain\Exception\UserException;
use App\Http\Context\LocaleContext;
use App\Infrastructure\Service\CsrfTokenService;
use App\Infrastructure\Translation\LanguageTranslator;
use Exception;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\User\CreateUserUseCase;
use App\Application\UseCase\User\DeleteUserUseCase;
use App\Infrastructure\Security\AuthenticationService;

class UserController extends BaseController
{
    public function __construct(
        AuthenticationService $authenticationService,
        CsrfTokenService $csrfTokenService,
        LoggerInterface $logger,
        LanguageTranslator $translator,
        LocaleContext $localeContext,
        private readonly CreateUserUseCase $createUserUseCase,
        private readonly DeleteUserUseCase $deleteUserUseCase,
    ) {
        parent::__construct($authenticationService, $csrfTokenService, $logger, $translator, $localeContext);
    }

    /**
     * Create a new user
     *
     * @throws UserException
     * @throws Exception
     */
    public function addUser(): void
    {
        $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
        $password = trim((string)filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW));

        if (empty($username) || empty($password)) {
            throw UserException::emptyFields();
        }

        $this->createUserUseCase->execute($username, $password);

        $this->logger->info("User created successfully", ['username' => $username]);
        $this->successAndRedirect('user.created_successfully', '/panel/users');
    }

    /**
     * Delete a user
     *
     * @throws UserException
     * @throws Exception
     */
    public function deleteUser(): void
    {
        $userToDeleteId = (int)filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if (!$userToDeleteId || $userToDeleteId <= 0) {
            throw UserException::invalidId();
        }

        $currentUserId = $this->getCurrentUserId();

        if (!$currentUserId) {
            throw UserException::unauthorized();
        }

        $this->deleteUserUseCase->execute($currentUserId, $userToDeleteId);

        $this->logger->info("User deleted successfully", ['deleted_id' => $userToDeleteId, 'deleted_by' => $currentUserId]);
        $this->successAndRedirect('user.deleted_successfully', '/panel/users');
    }
}
