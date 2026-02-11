<?php

namespace App\Presentation\Http\Controller;

use App\Application\User\AuthenticateUserDTO;
use App\Application\User\UseCase\AuthenticateUserUseCase;
use App\Domain\Shared\AuthenticationException;
use App\Infrastructure\Helper\SessionHelper;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\FlashMessengerInterface;
use App\Presentation\Http\Shared\ViewRendererInterface;
use App\Presentation\View\TemplateNames;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class LoginController extends BaseController
{
    function __construct(
         RequestContext $requestContext,
         ViewRendererInterface $renderer,
        readonly FlashMessengerInterface            $flash,
        readonly LoggerInterface                    $logger,
        private readonly ServerRequestInterface     $request,
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
    public function authenticate(): ResponseInterface
    {
        $this->logger->debug("User verification request received.");

        $body = json_decode((string) $this->request->getBody(), true);

        $dto = AuthenticateUserDTO::fromArray($body);

        $user = $this->authenticateUserUseCase->execute($dto);

        $this->logger->debug("Correct password for given username.");
        SessionHelper::start();
        SessionHelper::regenerateId();
        SessionHelper::setWithFingerprint('user_id', $user->id);
        $this->logger->debug("Redirecting shortly to panel.");

        return $this->jsonResponse(200, [
            'success' => true,
            'redirect' => '/panel',
        ]);
    }

    public function logout(): ResponseInterface
    {
        $this->logger->debug("User logout requested.");
        SessionHelper::destroy();
        return $this->redirect("/login");
    }

}
