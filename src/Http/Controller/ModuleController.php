<?php

namespace App\Http\Controller;

use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Module\GetModuleByIdUseCase;
use App\Application\UseCase\Module\ToggleModuleUseCase;
use App\Application\UseCase\Module\UpdateModuleUseCase;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Security\CsrfService;

class ModuleController extends BaseController
{
    public function __construct(
        AuthenticationService $authenticationService,
        CsrfService $csrfService,
        LoggerInterface $logger,
        private readonly GetModuleByIdUseCase $getModuleByIdUseCase,
        private readonly ToggleModuleUseCase  $toggleModuleUseCase,
        private readonly UpdateModuleUseCase  $updateModuleUseCase,
    )
    {
        parent::__construct($authenticationService, $csrfService, $logger);
    }
    public function toggleModule(): void
    {
        try {
            $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
            $enable = filter_input(INPUT_POST, 'is_active', FILTER_UNSAFE_RAW);

            if (!$moduleId || !isset($enable)) {
                $this->redirect("/panel");
            }

            $this->toggleModuleUseCase->execute($moduleId);
            $this->redirect("/panel");

        } catch (Exception $e) {
            $this->handleError("Failed to toggle module", "Failed to toggle module: " . $e->getMessage(), "/panel");
        }
    }

    public function editModule(): void {
            try {
                $moduleId = (int)filter_input(INPUT_POST, 'module_id', FILTER_VALIDATE_INT);
                $newModuleStartTime = trim((string)filter_input(INPUT_POST, 'start_time', FILTER_UNSAFE_RAW));
                $newModuleEndTime = trim((string)filter_input(INPUT_POST, 'end_time', FILTER_UNSAFE_RAW));
                $newModuleIsActive = isset($_POST['is_active']) ? 1 : 0;

                $module = $this->getModuleByIdUseCase->execute($moduleId);

                if (empty($module)) {
                    throw new Exception("Module not found");
                }

                $dateFormat = 'H:i';

                $normalizedStart = $this->normalizeTime($newModuleStartTime, $module->startTime, $dateFormat);
                $normalizedEnd = $this->normalizeTime($newModuleEndTime, $module->endTime, $dateFormat);

                $updates = [
                    'module_name' => $module->moduleName,
                    'is_active' => $newModuleIsActive,
                    'start_time' => $normalizedStart,
                    'end_time' => $normalizedEnd,
                ];

                $this->updateModuleUseCase->execute($moduleId, $updates);

                $this->redirect('/panel/modules');
            } catch (Exception $e) {
                $this->handleError("Failed to edit module", "Module edit failed " . $e->getMessage(), "/panel/modules");
            }
    }

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