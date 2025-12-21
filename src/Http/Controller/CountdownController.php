<?php

namespace App\Http\Controller;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use App\Application\UseCase\Countdown\CreateCountdownUseCase;
use App\Application\UseCase\Countdown\DeleteCountdownUseCase;
use App\Application\UseCase\Countdown\GetCountdownByIdUseCase;
use App\Application\UseCase\Countdown\UpdateCountdownUseCase;
use App\Infrastructure\Security\AuthenticationService;
use App\Infrastructure\Security\CsrfService;

class CountdownController extends BaseController
{
    public function __construct(
        AuthenticationService $authenticationService,
        CsrfService $csrfService,
        LoggerInterface $logger,
        private readonly CreateCountdownUseCase $createCountdownUseCase,
        private readonly DeleteCountdownUseCase $deleteCountdownUseCase,
        private readonly GetCountdownByIdUseCase $getCountdownByIdUseCase,
        private readonly UpdateCountdownUseCase $updateCountdownUseCase,
    )
    {
        parent::__construct($authenticationService, $csrfService, $logger);
    }
    public function addCountdown() : void
    {
        try {
            $title = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
            $count_to = (string)filter_input(INPUT_POST, 'count_to', FILTER_UNSAFE_RAW);
            $userId = $this->getCurrentUserId();

            if ($title === '' || $count_to === '') {
                $this->handleError('All fields must be filled.', "All fields must be filled." , "/panel/countdowns");
            }

            $data = ['title' => $title, 'count_to' => $count_to];

            $this->createCountdownUseCase->execute($data, $userId);
            $this->handleSuccess('Countdown created.', 'Countdown created.', '/panel/countdowns');
        } catch (Exception $e) {
            $this->handleError("Failed to add countdown", "Countdown adding failed: " . $e->getMessage(), "/panel/countdowns");
        }
    }


    public function editCountdown(): void
    {
        try {
            $newCountdownTitle = trim((string)filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW));
            $newRawCountdownCountTo = (string)filter_input(INPUT_POST, 'count_to', FILTER_UNSAFE_RAW);
            $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

            $countdown = $this->getCountdownByIdUseCase->execute($countdownId);
            if (empty($countdown)) {
                throw new Exception("Countdown not found");
            }

            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $newRawCountdownCountTo);
            if (!$dt) {
                throw new Exception("Invalid date format");
            }
            $newCountdownCountTo = $dt->format('Y-m-d H:i:s');

            $updates = [];

            if ($newCountdownTitle !== '' && $newCountdownTitle !== $countdown->title) {
                $updates['title'] = $newCountdownTitle;
            }

            if ($newCountdownCountTo != $countdown->countTo->format('Y-m-d H:i:s')) {
                $updates['count_to'] = $newCountdownCountTo;
            }

            if (!empty($updates)) {
                $this->updateCountdownUseCase->execute($countdownId, $updates);
                    $this->handleSuccess('Countdown updated.', "Updated countdown fields", '/panel/countdowns');
                } else {
                    $this->handleError('No changes were made.', 'Error while editing countdown', '/panel/countdowns');
                }
        } catch (Exception $e) {
            $this->handleError("Failed to update countdown", "Countdown update failed: " . $e->getMessage(), "/panel/countdowns");
        }
    }

    public function deleteCountdown(): void
    {
        try {
            $countdownId = (int)filter_input(INPUT_POST, 'countdown_id', FILTER_VALIDATE_INT);

            $this->deleteCountdownUseCase->execute($countdownId);

            $this->handleSuccess("Countdown deleted successfully", "Countdown deleted successfully", '/panel/countdowns');
        } catch (Exception $e) {
            $this->handleError("Failed to delete countdown", "Countdown deletion failed: " . $e->getMessage(), "/panel/countdowns");
        }
    }
}