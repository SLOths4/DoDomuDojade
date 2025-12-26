<?php

namespace App\Http\Controller;

use App\Application\DataTransferObject\EditModuleDTO;
use App\Domain\Exception\ModuleException;
use App\Http\Context\RequestContext;
use App\Infrastructure\Service\FlashMessengerInterface;
use App\Infrastructure\View\ViewRendererInterface;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Module\ToggleModuleUseCase;
use App\Application\UseCase\Module\UpdateModuleUseCase;

final class ModuleController extends BaseController
{
    public function __construct(
        readonly RequestContext $requestContext,
        readonly ViewRendererInterface $renderer,
        readonly FlashMessengerInterface $flash,
        private readonly LoggerInterface $logger,
        private readonly ToggleModuleUseCase $toggleModuleUseCase,
        private readonly UpdateModuleUseCase $updateModuleUseCase,
    ){}

    /**
     * Toggle module active/inactive status
     * @throws ModuleException
     * @throws Exception
     */
    #[NoReturn]
    public function toggleModule(): void
    {
        $this->logger->debug("Received toggle module request");
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        $this->toggleModuleUseCase->execute($moduleId);

        $this->flash('success', 'announcement.toggled_successfully');
        $this->redirect('/panel/modules');
    }

    /**
     * Edit module settings (times and active status)
     * @throws Exception
     */
    #[NoReturn]
    public function editModule(): void
    {
        $this->logger->debug("Received edit module request");
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        $dto = EditModuleDTO::fromHttpRequest($_POST);

        $this->updateModuleUseCase->execute($moduleId, $dto);

        $this->flash('success', 'announcement.updated_successfully');
        $this->redirect('/panel/modules');
    }
}
