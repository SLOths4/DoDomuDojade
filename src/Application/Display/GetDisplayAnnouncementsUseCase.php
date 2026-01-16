<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Application\Announcement\GetValidAnnouncementsUseCase;
use App\Application\User\GetUserByIdUseCase;
use Psr\Log\LoggerInterface;
use Exception;

readonly class GetDisplayAnnouncementsUseCase
{
    public function __construct(
        private GetValidAnnouncementsUseCase $getValidAnnouncementsUseCase,
        private GetUserByIdUseCase $getUserByIdUseCase,
        private LoggerInterface $logger
    ) {}

    public function execute(): array
    {
        $announcements = $this->getValidAnnouncementsUseCase->execute();

        $response = [];
        foreach ($announcements as $announcement) {
            $author = 'Nieznany użytkownik';

            if (!is_null($announcement->getUserId())) {
                try {
                    $user = $this->getUserByIdUseCase->execute($announcement->getUserId());
                    $author = $user->username;
                } catch (Exception $e) {
                    $this->logger->warning("Failed to fetch announcement author", [
                        'userId' => $announcement->getUserId(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $response[] = [
                'title' => $announcement->getTitle(),
                'author' => $author,
                'text' => $announcement->getText(),
            ];
        }

        return $response;
    }
}
