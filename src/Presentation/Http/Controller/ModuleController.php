<?php

namespace App\Presentation\Http\Controller;

use App\Application\Module\EditModuleDTO;
use App\Application\Module\UseCase\ToggleModuleUseCase;
use App\Application\Module\UseCase\UpdateModuleUseCase;
use App\Presentation\Http\Context\RequestContext;
use App\Presentation\Http\Shared\Translator;
use App\Presentation\Http\Shared\ViewRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ModuleController extends BaseController
{
    public function __construct(
         RequestContext                     $requestContext,
         ViewRendererInterface              $renderer,
         private readonly ServerRequestInterface $request,
        private readonly LoggerInterface $logger,
        private readonly Translator $translator,
        private readonly ToggleModuleUseCase $toggleModuleUseCase,
        private readonly UpdateModuleUseCase $updateModuleUseCase,
    ) {
        parent::__construct($requestContext, $renderer);
    }

    /**
     * Toggle module via API
     * POST /api/module/{id}/toggle
     * @throws \Exception
     */
    public function toggle(array $vars = []): ResponseInterface
    {
        $this->logger->debug("Received toggle module request");
        $moduleId = (int)$vars['id'];

        $this->toggleModuleUseCase->execute($moduleId);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('module.toggled_successfully'),
        ]);
    }

    /**
     * Update module via API
     * PATCH /api/module/{id}
     * @throws \Exception
     */
    public function update(array $vars = []): ResponseInterface
    {
        $this->logger->debug("Received update module request");
        $moduleId = (int)$vars['id'];
        $body = json_decode((string)$this->request->getBody(), true);

        $dto = EditModuleDTO::fromArray($body);

        $this->updateModuleUseCase->execute($moduleId, $dto);

        return $this->jsonResponse(200, [
            'success' => true,
            'message' => $this->translator->translate('module.updated_successfully'),
        ]);
    }
}
