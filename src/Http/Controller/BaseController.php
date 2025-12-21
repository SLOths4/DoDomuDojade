<?php

namespace App\Http\Controller;

use App\Infrastructure\Exception\ValidationException;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use App\Infrastructure\Exception\UserException;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Security\CsrfService;

class BaseController{
    public function __construct(
        protected readonly AuthenticationService $authenticationService,
        protected readonly CsrfService $csrfService,
        protected readonly LoggerInterface $logger
    ){}

    protected function render($view, $data = []): void
    {
        $data['VIEWS_PATH'] = dirname(__DIR__, 2) . '/Presentation/';
        $data['footer'] = $data['footer'] ?? false;
        $data['navbar'] = $data['navbar'] ?? false;

        extract($data);

        $file = $data['VIEWS_PATH'] . "$view.php";
        if (file_exists($file)) {
            include $file;
        } else {
            echo "View not found: $file";
        }
    }

    #[NoReturn]
    protected function redirect(string $to): void
    {
        header("Location: $to");
        exit;
    }

    protected function getCurrentUserId(): ?int
    {
        return $this->authenticationService->getCurrentUserId();
    }

    #[NoReturn]
    public function logout(): void
    {
        $this->authenticationService->logout();
        $this->redirect('/login');
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
