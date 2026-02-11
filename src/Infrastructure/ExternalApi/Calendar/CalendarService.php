<?php

namespace App\Infrastructure\ExternalApi\Calendar;

use Google\Service\Calendar\Events;
use Google_Client;
use Google_Service_Calendar;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Used to interact with google calendar interface
 */
readonly class CalendarService
{
    public function __construct(
        private LoggerInterface $logger,
        private string $googleCalendarApiKey,
        private string $googleCalendarId
    ) {}

    private function createClient(): Google_Client
    {
        try {
            $client = new Google_Client();
            $client->setApplicationName("DoDomuDojade - Calendar Integration");

            $this->logger->debug('Google Client successfully initialized');
            return $client;

        } catch (Throwable $e) {
            $this->logger->error('Failed to create Google Client', ['error' => $e->getMessage()]);
            throw CalendarApiException::clientInitializationFailed($e);
        }
    }

    private function authenticateClient(Google_Client $client): Google_Client
    {
        try {
            if (empty($this->googleCalendarApiKey)) {
                throw CalendarApiException::invalidApiKey();
            }

            $client->setAuthConfig($this->googleCalendarApiKey);
            $this->logger->debug('Google API authenticated successfully');

            return $client;

        } catch (Throwable $e) {
            $this->logger->error('Google API authentication failed', ['error' => $e->getMessage()]);
            throw CalendarApiException::authenticationFailed($e);
        }
    }

    public function getEvents(): Events
    {
        try {
            $client = $this->createClient();
            $client = $this->authenticateClient($client);
            $client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);

            $service = new Google_Service_Calendar($client);

            $this->logger->debug('Google Calendar service initialized');

            $params = [
                'timeMin' => date('c', strtotime('today')),
                'timeMax' => date('c', strtotime('tomorrow')),
                'orderBy' => 'startTime',
                'singleEvents' => true,
            ];

            $this->logger->debug('Fetching calendar events', ['calendar_id' => $this->googleCalendarId]);

            $events = $service->events->listEvents($this->googleCalendarId, $params);

            $this->logger->info('Calendar events successfully fetched');

            return $events;

        } catch (Throwable $e) {
            $this->logger->error('Failed to fetch calendar events', ['error' => $e->getMessage()]);
            throw CalendarApiException::fetchingEventsFailed($e);
        }
    }
}
