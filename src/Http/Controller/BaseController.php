<?php
namespace App\Http\Controller;

use App\Infrastructure\Translation\LanguageTranslator;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Security\AuthenticationService;
use App\Http\Context\LocaleContext;
use App\Infrastructure\Service\CsrfTokenService;
use Random\RandomException;

class BaseController
{
    public function __construct(
        protected readonly AuthenticationService $authenticationService,
        protected readonly CsrfTokenService $csrfTokenService,
        protected readonly LoggerInterface $logger,
        protected readonly LanguageTranslator $translator,
        protected readonly LocaleContext $localeContext
    ) {}

    /**
     * Render a view with data
     * Automatically handles error/success messages from the session
     * @throws RandomException
     */
    protected function render($view, $data = []): void
    {
        $data['VIEWS_PATH'] = dirname(__DIR__, 2) . '/Presentation/';
        $data['footer'] = $data['footer'] ?? false;
        $data['navbar'] = $data['navbar'] ?? false;
        $data['csrf_token'] = $this->csrfTokenService->getOrCreate();

        SessionHelper::start();

        $errorKey = SessionHelper::get('error');
        $successKey = SessionHelper::get('success');

        SessionHelper::remove('error');
        SessionHelper::remove('success');

        $data['error'] = $errorKey ? $this->translator->trans($errorKey) : null;
        $data['success'] = $successKey ? $this->translator->trans($successKey) : null;

        $data['locale'] = $this->localeContext->get();

        extract($data);

        $file = $data['VIEWS_PATH'] . "$view.php";
        if (file_exists($file)) {
            include $file;
        } else {
            echo "View not found: $file";
        }
    }

    /**
     * Redirect to URL
     */
    #[NoReturn]
    protected function redirect(string $to): void
    {
        header("Location: $to", true);
        exit;
    }

    /**
     * Get currently authenticated user ID
     */
    protected function getCurrentUserId(): ?int
    {
        return $this->authenticationService->getCurrentUserId();
    }

    /**
     * Logout current user
     */
    #[NoReturn]
    public function logout(): void
    {
        $this->authenticationService->logout();
        $this->redirect('/login');
    }

    /**
     * Set a success message and redirect
     * Use this for successful operations in controllers
     *
     * Example:
     * $this->successAndRedirect('announcement.created_successfully', '/panel/announcements');
     */
    #[NoReturn]
    protected function successAndRedirect(string $successKey, string $redirectTo): void
    {
        SessionHelper::start();
        SessionHelper::set('success', $successKey);
        $this->logger->info("Success: $successKey");

        $this->redirect($redirectTo);
    }
}
