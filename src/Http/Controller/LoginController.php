<?php

namespace App\Http\Controller;

use App\Application\UseCase\User\GetUserByUsernameUseCase;
use App\Domain\Exception\AuthenticationException;
use App\Http\Context\LocaleContext;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Service\CsrfTokenService;
use App\Infrastructure\Translation\LanguageTranslator;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;

final class LoginController extends BaseController
{

    function __construct(
        AuthenticationService                       $authenticationService,
        CsrfTokenService                            $csrfTokenService,
        LoggerInterface                             $logger,
        LanguageTranslator                          $translator,
        LocaleContext                               $localeContext,
        private readonly GetUserByUsernameUseCase   $getUserByUsernameUseCase,
    )
    {
        parent::__construct($authenticationService, $csrfTokenService, $logger, $translator, $localeContext);
    }

    public function show(): void
    {
        $this->render('pages/login', [
            'navbar' => false,
            'footer' => true,
        ]);
    }

    /**
     * @throws AuthenticationException
     * @throws Exception
     */
    public function authenticate(): void
    {
        $this->logger->debug("User verification request received.");

        $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
        $password = trim((string)filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW));

        if ($password === '' || $username === '') {
            throw AuthenticationException::emptyCredentials();
        }

        $user = $this->getUserByUsernameUseCase->execute($username);

        if ($user && password_verify($password, $user->passwordHash)) {
            $this->logger->debug("Correct password for given username.");
            SessionHelper::start();
            SessionHelper::setWithFingerprint('user_id', $user->id);
            $this->redirect("/panel");
        } else {
            throw AuthenticationException::invalidCredentials();
        }
    }

    #[NoReturn]
    public function logout(): void
    {
        $this->logger->debug("User logout requested.");
        $userId = SessionHelper::get('user_id');

        SessionHelper::destroy();

        $this->logger->debug("User logged out", ['userId' => $userId]);
        $this->redirect("/login");
    }
}
