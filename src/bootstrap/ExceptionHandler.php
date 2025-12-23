<?php
// src/bootstrap/ExceptionHandler.php
namespace App\bootstrap;

use App\Domain\Exception\DomainException;
use App\Http\Controller\ErrorController;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Translation\Translator;
use JetBrains\PhpStorm\NoReturn;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Throwable;

final readonly class ExceptionHandler
{
    public function __construct(
        private ContainerInterface $container,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(Throwable $e): void
    {
        $this->logger->error(
            sprintf(
                'Exception: %s (%s:%d)',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ),
            $e->getTrace()
        );

        if ($e instanceof DomainException) {
            SessionHelper::start();
            SessionHelper::set('error', $e->getMessage());

            $this->redirectBasedOnException($e);
        } else {
            http_response_code(500);
            $this->container->get(ErrorController::class)->internalServerError();
        }
    }

    #[NoReturn]
    private function redirectBasedOnException(DomainException $e): void
    {
        $domainClass = new ReflectionClass($e)->getShortName();

        $redirectMap = [
            'AuthenticationException' => '/login',
            'AuthorizationException' => '/login',
            'PanelException' => '/panel',
            'AnnouncementException' => '/panel/announcements',
            'UserException' => '/panel/users',
        ];

        $redirectTo = $redirectMap[$domainClass] ?? '/';
        header("Location: $redirectTo");
        exit;
    }
}
