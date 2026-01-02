<?php

namespace App\Infrastructure\Service;

require  __DIR__ . '/../../../vendor/autoload.php';

use Exception;
use Google\Service\Calendar\Events;
use Google_Client;
use Google_Service_Calendar;
use Psr\Log\LoggerInterface;

readonly class CalendarService
{
    public function __construct(
        private LoggerInterface              $logger,
        private string                       $googlecalendarApiKey,
        private string                       $googlecalendarId
    ) {}

    private function googleclientSetup(): ?Google_Client
    {
        try {
            $client = new Google_Client();
            $this->logger->debug('Google Client successfully set up');
            $client->setApplicationName("DDDCalendarIntegration");
            $this->logger->debug('Application Name successfully set up');
            return $client;
        } catch (Exception $e) {
            $this->logger->error('Failed to create Google Client: ' . $e->getMessage());
            return null;
        }

    }

    private function googlecalendarAuth(Google_Client $client): ?Google_Client
    {
        try {
            $client->setAuthConfig(__DIR__ . '/../../../' . $this->googlecalendarApiKey);
            $this->logger->debug('Google API authenticated successfully');
            return $client;
        } catch (Exception $e) {
            print $e->getMessage();
            $this->logger->error('Google API authentication error', ['exception' => $e]);
            return null;
        }
    }

    /**
     * @throws \Google\Service\Exception
     */
    public function getEvents(): Events
    {
        $clientfin = $this->googlecalendarAuth($this->googleclientSetup());
        $clientfin->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
        $service = new Google_Service_Calendar($clientfin);
        $this->logger->info('google cal service in getEvents method complete');

        $params = [
            'timeMin' => date('c', strtotime('today')),
            'timeMax' => date('c', strtotime('tomorrow')),
            'orderBy' => 'startTime',
            'singleEvents' => true,
        ];
        return $service->events->listEvents($this->googlecalendarId, $params);
    }
}