<?php

namespace src\core;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use src\infrastructure\helpers\SessionHelper;
use src\security\AuthenticationService;
use src\security\CsrfService;

class Controller{
    public function __construct(
        protected readonly AuthenticationService $authenticationService,
        protected readonly CsrfService $csrfService,
        protected readonly LoggerInterface $logger
    ){}

    protected function render($view, $data = []): void
    {
        extract($data);
        $file = __DIR__ . "/../views/$view.php";
        if (file_exists($file)) {
            include $file;
        } else {
            echo "Plik widoku nie został znaleziony: $file";
        }
    }

    #[NoReturn]
    protected function redirect(string $to): void
    {
        header("Location: $to");
        exit;
    }

    /**
     * @throws Exception
     */
    protected function requireAuth(): void
    {
        if (!$this->authenticationService->isUserLoggedIn()) {
            // TODO Dodać exception do obsługi niezalogowany user -ów
            throw new Exception("No user logged in!");
        }
    }

    protected function getCurrentUserId(): ?int
    {
        return $this->authenticationService->getCurrentUserId();
    }

    protected function checkIsUserLoggedIn(): void
    {
        $userId = SessionHelper::get('user_id');
        if (!$userId) {
            $this->handleError("Please sign in to continue", "No user logged in");
        }
    }

    #[NoReturn]
    public function logout(): void
    {
        $this->authenticationService->logout();
        $this->redirect('/login');
    }

    /**
     * @throws Exception
     */
    protected function validateCsrf(string $token): void
    {
        if (!$this->csrfService->validateCsrf($token, SessionHelper::get('csrf_token'))) {
            // TODO Add incorrect csrf exception
            throw new Exception("Incorrect token");
        }
    }

    /**
     * @throws RandomException
     */
    protected function setCsrf(): void
    {
        if (!SessionHelper::has('csrf_token')) {
            SessionHelper::set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    /**
     * @throws Exception
     */
    protected function validateMethod(string $usedMethod, string $expectedMethod): void
    {
        if ($usedMethod !== $expectedMethod) {
            // TODO Dodać MethodException
            throw new Exception("Incorrect method used!");
        }
    }

    #[NoReturn]
    protected function handleError(string $visibleMessage, string $logMessage, string $redirectTo = '/login'): void
    {
        $this->logger->error($logMessage);
        SessionHelper::set('error', $visibleMessage);
        $this->redirect($redirectTo);
    }

    #[NoReturn]
    protected function handleSuccess(string $visibleMessage, string $infoMessage, string $redirectTo = '/panel'): void
    {
        $this->logger->info($infoMessage);
        SessionHelper::set('success', $visibleMessage);
        $this->redirect($redirectTo);
    }
}
