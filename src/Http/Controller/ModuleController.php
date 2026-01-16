<?php

namespace App\Http\Controller;

use App\Application\Module\EditModuleDTO;
use App\Application\Module\ToggleModuleUseCase;
use App\Application\Module\UpdateModuleUseCase;
use App\Domain\Module\ModuleException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use Predis\Client;

use Psr\Http\Message\ResponseInterface;

final class ModuleController extends BaseController
{
    public function __construct(
        readonly RequestContext $requestContext,
        readonly ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
        private readonly LoggerInterface $logger,
        private readonly ToggleModuleUseCase $toggleModuleUseCase,
        private readonly UpdateModuleUseCase $updateModuleUseCase,
        private readonly Client $redis,
    ){}

    /**
     * Toggle module active/inactive status
     * @throws ModuleException
     * @throws Exception
     */
    public function toggleModule(): ResponseInterface
    {
        $this->logger->debug("Received toggle module request");
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        $this->toggleModuleUseCase->execute($moduleId);

        $this->flash('success', 'module.toggled_successfully');
        return $this->redirect('/panel/modules');
    }

    /**
     * Edit module settings (times and active status)
     * @throws Exception
     */
    public function editModule(): ResponseInterface
    {
        $this->logger->debug("Received edit module request");
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        $dto = EditModuleDTO::fromHttpRequest($_POST);

        $this->updateModuleUseCase->execute($moduleId, $dto);

        $this->flash('success', 'module.updated_successfully');
        return $this->redirect('/panel/modules');
    }
}
