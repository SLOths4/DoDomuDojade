<?php

namespace App\Presentation\Http\Controller;

use App\Application\Module\EditModuleDTO;
use App\Application\Module\UseCase\ToggleModuleUseCase;
use App\Application\Module\UseCase\UpdateModuleUseCase;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class ModuleController extends BaseController
{
    public function __construct(
         RequestContext                     $requestContext,
         ViewRendererInterface              $renderer,
        private readonly LoggerInterface $logger,
        private readonly Translator $translator,
        private readonly ToggleModuleUseCase $toggleModuleUseCase,
        private readonly UpdateModuleUseCase $updateModuleUseCase,
    ) {
        parent::__construct($requestContext, $renderer);
    }

    public function toggleModule(): ResponseInterface
    {
        $this->logger->debug("Received toggle module request");
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        $this->toggleModuleUseCase->execute($moduleId);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('module.toggled_successfully'),
        ]);
    }

    public function editModule(): ResponseInterface
    {
        $this->logger->debug("Received edit module request");
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        $dto = EditModuleDTO::fromHttpRequest($_POST);

        $this->updateModuleUseCase->execute($moduleId, $dto);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('module.updated_successfully'),
        ]);
    }
}
