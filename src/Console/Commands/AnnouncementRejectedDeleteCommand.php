<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Announcement\UseCase\DeleteRejectedSinceAnnouncementUseCase;
use App\Console\Command;
use App\Console\ConsoleOutput;
use Exception;

/**
 * Deletes rejected announcements since a provided date
 */
final readonly class AnnouncementRejectedDeleteCommand implements Command
{
    /**
     * @param DeleteRejectedSinceAnnouncementUseCase $useCase
     */
    public function __construct(
        private DeleteRejectedSinceAnnouncementUseCase $useCase
    ) {}

    /**
     * @inheritDoc
     */
    public function execute(array $arguments, ConsoleOutput $output): void
    {
        $date = $arguments[0];

        $output->info("Deleting rejected announcements since $date...");

        try {
            $numOfDeleted = $this->useCase->execute($date);
            if ($numOfDeleted == 0) {
                $output->success("No announcements to delete!");
                return;
            }
            $output->success("$numOfDeleted announcements deleted successfully!");
        } catch (Exception $e) {
            $output->error("Failed to delete announcements: " . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'announcement-rejected:delete';
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Delete rejected announcements since given date';
    }

    /**
     * @inheritDoc
     */
    public function getArgumentsCount(): int
    {
        return 1;
    }
}
