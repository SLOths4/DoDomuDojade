<?php
declare(strict_types=1);

namespace App\Application\Display;

use App\Application\Announcement\UseCase\GetValidAnnouncementsUseCase;
use App\Application\User\UseCase\GetUserByIdUseCase;
use App\Domain\Shared\EntityNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Provides announcements data formatted for display page
 */
readonly class GetDisplayAnnouncementsUseCase
{
    public function __construct(
        private GetValidAnnouncementsUseCase $getValidAnnouncementsUseCase,
        private GetUserByIdUseCase $getUserByIdUseCase,
        private LoggerInterface $logger
    ) {}

    /**
     * @return array<int, array{title: string, author: string, text: string}>
     */
    public function execute(): array
    {
        $this->logger->debug('Fetching announcements for display');
        $announcements = $this->getValidAnnouncementsUseCase->execute();

        $response = [];
        foreach ($announcements as $announcement) {
            $author = 'Nieznany użytkownik';

            if (!is_null($announcement->getUserId())) {
                try {
                    $user = $this->getUserByIdUseCase->execute($announcement->getUserId());
                    $author = $user->username;
                } catch (EntityNotFoundException $e) {
                    $this->logger->warning('Failed to fetch announcement author', [
                        'user_id' => $announcement->getUserId(),
                        'announcement_title' => $announcement->title,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $response[] = [
                'title' => $announcement->title,
                'author' => $author,
                'text' => $announcement->text,
            ];
        }

        $this->logger->debug('Announcements prepared for display', [
            'announcement_count' => count($response),
        ]);

        return $response;
    }
}
