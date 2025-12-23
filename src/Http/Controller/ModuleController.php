<?php

namespace App\Http\Controller;

use App\Domain\Exception\ModuleException;
use App\Http\Context\LocaleContext;
use App\Infrastructure\Translation\LanguageTranslator;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Module\GetModuleByIdUseCase;
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
        private readonly GetModuleByIdUseCase $getModuleByIdUseCase,
        private readonly ToggleModuleUseCase $toggleModuleUseCase,
        private readonly UpdateModuleUseCase $updateModuleUseCase,
    ) {
        parent::__construct($authenticationService, $csrfTokenService, $logger, $translator, $localeContext);
    }

    /**
     * Toggle module active/inactive status
     *
     * @throws ModuleException
     * @throws Exception
     */
    public function toggleModule(): void
    {
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        if (!$moduleId || $moduleId <= 0) {
            throw ModuleException::invalidId();
        }

        $this->toggleModuleUseCase->execute($moduleId);

        $this->logger->info("Module toggled", ['id' => $moduleId]);
        $this->successAndRedirect('module.toggled_successfully', '/panel/modules');
    }

    /**
     * Edit module settings (times and active status)
     *
     * @throws ModuleException
     * @throws Exception
     */
    public function editModule(): void
    {
        $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);

        if (!$moduleId || $moduleId <= 0) {
            throw ModuleException::invalidId();
        }

        $newStartTime = trim((string)filter_input(INPUT_POST, 'start_time', FILTER_UNSAFE_RAW));
        $newEndTime = trim((string)filter_input(INPUT_POST, 'end_time', FILTER_UNSAFE_RAW));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $module = $this->getModuleByIdUseCase->execute($moduleId);
        if (!$module) {
            throw ModuleException::notFound($moduleId);
        }

        $normalizedStart = $this->normalizeTime($newStartTime, $module->startTime, 'H:i');
        $normalizedEnd = $this->normalizeTime($newEndTime, $module->endTime, 'H:i');

        $updates = [
            'module_name' => $module->moduleName,
            'is_active' => $isActive,
            'start_time' => $normalizedStart,
            'end_time' => $normalizedEnd,
        ];

        $this->updateModuleUseCase->execute($moduleId, $updates);

        $this->logger->info("Module updated", ['id' => $moduleId]);
        $this->successAndRedirect('module.updated_successfully', '/panel/modules');
    }

    /**
     * Normalize time input with fallback
     *
     * Handles multiple time formats: H:i, H:i:s, and generic datetime strings
     * Returns in H:i format
     *
     * @param string $value Input time value
     * @param DateTimeImmutable $fallback Fallback time if parsing fails
     * @param string $dateFormat Output format
     * @return string Normalized time in requested format
     */
    private function normalizeTime(string $value, DateTimeImmutable $fallback, string $dateFormat): string
    {
        $value = trim($value);

        if ($value === '') {
            return $fallback->format($dateFormat);
        }

        $candidates = ['H:i', 'H:i:s'];
        foreach ($candidates as $fmt) {
            $dt = DateTimeImmutable::createFromFormat($fmt, $value);
            if ($dt instanceof DateTimeImmutable) {
                return $dt->format($dateFormat);
            }
        }

        try {
            $dt = new DateTimeImmutable($value);
            return $dt->format($dateFormat);
        } catch (Exception) {
            return $fallback->format($dateFormat);
        }
    }
}
