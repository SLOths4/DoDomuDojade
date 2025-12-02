<?php

namespace App\Http\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\UserService;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Security\CsrfService;

class UserController extends BaseController
{
    public function __construct(
        AuthenticationService $authenticationService,
        CsrfService $csrfService,
        LoggerInterface $logger,
        private readonly UserService $userService,
    )
    {
        parent::__construct($authenticationService, $csrfService, $logger);
    }

    public function addUser(): void
    {
        try {
            $this->validateCsrf($_POST['csrf_token']);
            $this->checkIsUserLoggedIn();

            $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
            $password = trim((string)filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW));


                $this->logger->debug("add_user request received");

                if ($username === '' || $password === '') {
                    $this->logger->error("Username and password are required");
                    SessionHelper::set('error', 'Username and password are required.');
                    $this->redirect('/panel/users');
                }

                $data = [
                    'username' => $username,
                    'password' => $password
                ];

                $result = $this->userService->create($data);
                if ($result) {
                    $this->logger->info("User added successfully");
                    SessionHelper::set('success', 'User created.');
                } else {
                    $this->logger->error("User adding failed");
                    SessionHelper::set('error', 'Failed to add user.');
                }
                $this->redirect('/panel/users');
        } catch (Exception $e) {
                $this->handleError("Failed to add user", "User adding failed: " . $e->getMessage(), "/panel/users");
        }
    }

    public function deleteUser(): void
    {
        try {
            $this->validateCsrf($_POST['csrf_token']);
            $userToDelete = (int)filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

            $userId = $this->getCurrentUserId();

            if ($userId === $userToDelete) {
                $this->handleError('User cannot delete themselves.', '/panel/users');
            }

            $result = $this->userService->delete($userId, $userToDelete);

            if ($result) {
                $this->logger->info("User deleted successfully");
                SessionHelper::set('success', 'User deleted.');
            } else {
                $this->logger->error("User deletion failed");
                SessionHelper::set('error', 'Failed to delete user.');
            }
            $this->redirect('/panel/users');
        } catch (Exception $e) {
            $this->handleError("User deletion failed", "User deletion failed: " . $e->getMessage(), "/panel/users");
        }
    }
}