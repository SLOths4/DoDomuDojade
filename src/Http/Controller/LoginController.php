<?php

namespace App\Http\Controller;

use App\Application\User\AuthenticateUserUseCase;
use App\Domain\Exception\AuthenticationException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;

final class LoginController extends BaseController
{
    function __construct(
        readonly RequestContext $requestContext,
        readonly ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
        readonly LoggerInterface            $logger,
        private readonly AuthenticateUserUseCase    $authenticateUserUseCase
    ) {}

    public function show(): void
    {
        $this->logger->debug("Render login page request received");
        $this->render('pages/login');
        $this->logger->debug("Rendered login page");
    }

    /**
     * @throws AuthenticationException
     * @throws Exception
     */
    #[NoReturn]
    public function authenticate(): void
    {
        $this->logger->debug("User verification request received.");

        $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
        $password = trim((string)filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW));

        $user = $this->authenticateUserUseCase->execute($username, $password);

        $this->logger->debug("Correct password for given username.");
        SessionHelper::start();
        SessionHelper::setWithFingerprint('user_id', $user->id);
        $this->redirect("/panel");
    }

    #[NoReturn]
    public function logout(): void
    {
        $this->logger->debug("User logout requested.");
        SessionHelper::destroy();
        $this->redirect("/login");
    }
}
