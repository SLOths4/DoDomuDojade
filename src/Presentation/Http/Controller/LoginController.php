<?php

namespace App\Presentation\Http\Controller;

use App\Application\User\AuthenticateUserUseCase;
use App\Domain\Shared\AuthenticationException;
use App\Infrastructure\Helper\SessionHelper;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\ViewRendererInterface;
use App\Presentation\View\TemplateNames;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class LoginController extends BaseController
{
    function __construct(
         RequestContext $requestContext,
         ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
        readonly LoggerInterface            $logger,
        private readonly AuthenticateUserUseCase    $authenticateUserUseCase
    ) {
        parent::__construct($requestContext, $renderer);
    }

    public function show(): ResponseInterface
    {
        $this->logger->debug("Render login page request received");
        return $this->render(TemplateNames::LOGIN->value);
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

        $user = $this->authenticateUserUseCase->execute($username, $password);

        $this->logger->debug("Correct password for given username.");
        SessionHelper::start();
        SessionHelper::setWithFingerprint('user_id', $user->id);
        $this->redirect("/panel");
    }

    public function logout(): void
    {
        $this->logger->debug("User logout requested.");
        SessionHelper::destroy();
        $this->redirect("/login");
    }
}
