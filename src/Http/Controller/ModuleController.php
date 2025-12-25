<?php

namespace App\Http\Controller;

use App\Application\DataTransferObject\EditModuleDTO;
use App\Domain\Exception\ModuleException;
use App\Http\Context\LocaleContext;
use App\Infrastructure\Translation\LanguageTranslator;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Module\ToggleModuleUseCase;
use App\Application\UseCase\Module\UpdateModuleUseCase;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Service\CsrfTokenService;

class ModuleController extends BaseController
{
    public function __construct(
        AuthenticationService $authenticationService,
        CsrfTokenService $csrfTokenService,
        LoggerInterface $logger,
        LanguageTranslator $translator,
        LocaleContext $localeContext,
        private readonly ToggleModuleUseCase $toggleModuleUseCase,
        private readonly UpdateModuleUseCase $updateModuleUseCase,
    ) {
        parent::__construct($authenticationService, $csrfTokenService, $logger, $translator, $localeContext);
    }

    /**
     * Toggle module active/inactive status
     * @throws ModuleException
     * @throws Exception
     */
    #[NoReturn]
    public function toggleModule(): void
    {
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        $this->toggleModuleUseCase->execute($moduleId);

        $this->successAndRedirect('module.toggled_successfully', '/panel/modules');
    }

    /**
     * Edit module settings (times and active status)
     * @throws Exception
     */
    #[NoReturn]
    public function editModule(): void
    {
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        $dto = EditModuleDTO::fromHttpRequest($_POST);

        $this->updateModuleUseCase->execute($moduleId, $dto);

        $this->successAndRedirect('module.updated_successfully', '/panel/modules');
    }
}
