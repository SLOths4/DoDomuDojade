<?php
declare(strict_types=1);

namespace App\Application\UseCase\Module;

use App\Domain\Module;
use App\Infrastructure\Repository\ModuleRepository;
use Psr\Log\LoggerInterface;
use DateTimeImmutable;
use Exception;

readonly class UpdateModuleUseCase
{
    public function __construct(
        private ModuleRepository $repository,
        private LoggerInterface $logger,
        private string $dateFormat
    ) {}

    /**
     * @throws Exception
     */
    public function execute(int $id, array $data): bool
    {
        $this->logger->info('Updating module', [
            'module_id' => $id,
            'payload_keys' => array_keys($data),
        ]);

        $this->validate($data);

        $module = $this->repository->findById($id);
        if ($module === null) {
            $this->logger->warning('Module does not exist', ['module_id' => $id]);
            throw new Exception("Module with id: $id does not exist");
        }

        $startTime = isset($data['start_time'])
            ? DateTimeImmutable::createFromFormat($this->dateFormat, $data['start_time'])
            : $module->startTime;

        $endTime = isset($data['end_time'])
            ? DateTimeImmutable::createFromFormat($this->dateFormat, $data['end_time'])
            : $module->endTime;

        $updatedModule = new Module(
            $id,
            $module->moduleName,
            isset($data['is_active']) ? (bool)$data['is_active'] : $module->isActive,
            $startTime,
            $endTime
        );

        $result = $this->repository->update($updatedModule);

        $this->logger->info('Module update finished', [
            'module_id' => $id,
            'success' => $result,
        ]);

        return $result;
    }

    /**
     * @throws Exception
     */
    private function validate(array $data): void
    {
        if (isset($data['start_time']) && isset($data['end_time'])) {
            $start = DateTimeImmutable::createFromFormat($this->dateFormat, $data['start_time']);
            $end = DateTimeImmutable::createFromFormat($this->dateFormat, $data['end_time']);

            if ($start && $end && $end <= $start) {
                $this->logger->warning('Invalid module time range', [
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                ]);
                throw new Exception('End time must be greater than start time');
            }
        }
    }
}
