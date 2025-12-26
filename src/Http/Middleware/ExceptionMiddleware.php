<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\DomainException;
use App\Http\Context\RequestContext;
use App\Http\ExceptionTranslator;
use App\Infrastructure\Helper\SessionHelper;
use App\Infrastructure\Service\FlashMessengerInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class ExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ExceptionTranslator     $translator,
        private LoggerInterface         $logger,
        private FlashMessengerInterface $flashMessengerService,
    ){}

    public function handle(Request $request, callable $next): void
    {
        try {
            $next($request);
        } catch (DomainException $e) {
            $this->logger->error(
                sprintf(
                    'Exception: %s (%s:%d)',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                $e->getTrace()
            );

            $this->flashMessengerService->flash('error', $e->getMessage());

            $currentPath = $request->getUri()->getPath();
            $redirectPath = $this->translator->getRedirectPath($e, $currentPath);

            $this->logger->info("Redirecting from $currentPath to $redirectPath");

            header("Location: $redirectPath");
            exit;
        } catch (Throwable $e) {
            $this->logger->critical(
                sprintf(
                    'Exception: %s (%s:%d)',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                $e->getTrace()
            );
            http_response_code(500);
            echo 'Internal Server Error';
            exit;
        }
    }
}
