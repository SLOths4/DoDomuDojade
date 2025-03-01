<?php
namespace src\models;

use DateMalformedStringException;
use DateTime;
use Exception;
use src\core\Model;

/**
 * iCal data parsing and customising class
 * @author Igor Woźnica <igor.supermemo@gmail.com>
 */
class CalendarModel extends Model
{
    private string $icalUrl;

    public function __construct(string $icalUrl) {
        $this->icalUrl = $icalUrl;
    }

    /**
     * iCal data fetching function
     * @return array
     * @throws Exception
     */
    public function get_events(): array
    {
        // Fetch the iCal data
        $icalData = @file_get_contents($this->icalUrl);

        if ($icalData === false) {
            $error = error_get_last();
            self::$logger->error("Error fetching iCal data: " . $error['message']);
            throw new Exception("Error fetching iCal data: " . $error['message']);
        } else {
            self::$logger->debug("Successfully fetched the iCal data");
        }
      
        if (!str_contains($icalData, 'BEGIN:VEVENT')) {
            self::$logger->debug("No events found in the iCal data.");
            return [];
        } else {
            self::$logger->debug("Found events in the iCal data.");
        }

        return $this->parse_ical_data($icalData);
    }

    /**
     * iCal events extracting function
     * @param array $icalData
     * @return array
     * @throws Exception
     */
    private function parse_ical_data(array $icalData): array
    {
        $events = [];
        preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $icalData, $matches);
        $currentDate = new DateTime();
    
        foreach ($matches[1] as $eventData) {
            $this->process_event($eventData, $events, $currentDate);
        }
        usort($events, function ($a, $b) {
            $dateA = DateTime::createFromFormat('H.i - d.m.Y', $a['start']);
            $dateB = DateTime::createFromFormat('H.i - d.m.Y', $b['start']);
            
            return $dateA <=> $dateB;
        });
        return $events;
    }

    /**
     * iCal event processing function
     * @param mixed $eventData
     * @param DateTime $currentDate
     * @param array $events
     * @return void
     * @throws Exception
     */
    private function process_event(mixed $eventData, array &$events, DateTime $currentDate): void
    {
        $event = $this->extract_event_data($eventData);
        $this->format_event_dates($event);
    
        try {
            $eventDate = DateTime::createFromFormat("Ymd\THis", substr($event['end1'], 0, 15));
            if (!$eventDate) throw new Exception("Invalid date format: {$event['end1']}");
    
            $daysUntilEvent = $this->calculate_days_until_event($eventDate, $currentDate);
            if ($this->should_include_event($eventDate, $currentDate, $daysUntilEvent)) {
                $events[] = $event;
            }
    
            if (!empty($event['rrule'])) {
                $startDate = DateTime::createFromFormat('Ymd\THis', substr($event['start'], 0, 15));
                $endDate = DateTime::createFromFormat('Ymd\THis', substr($event['end'], 0, 15));
                if ($startDate && $endDate) {
                    $this->generateRecurringEvents($events, $event, $startDate, $endDate);
                }
            }
        } catch (Exception $e) {
            self::$logger->error("Date parsing failed: " . $e->getMessage());
        }
    }

    /**
     * iCal event extracting function
     * @param string $eventData
     * @return array $event
     */
    private function extract_event_data(string $eventData): array
    {
        $event = [];
        preg_match('/SUMMARY:(.*)/', $eventData, $summary);
        preg_match('/DTSTART(.*)/', $eventData, $start);
        preg_match('/DTEND(.*)/', $eventData, $end);
        preg_match('/DESCRIPTION:(.*)/', $eventData, $description);
        preg_match('/RRULE:(.*)/', $eventData, $rrule);
    
        $event['summary'] = $summary[1] ?? '';
        $event['start'] = $start[1] ?? '';
        $event['end'] = $end[1] ?? '';
        $event['end1'] = $end[1] ?? '';  // Duplicate end time
        $event['description'] = $description[1] ?? '';
        $event['rrule'] = $rrule[1] ?? '';
    
        preg_match('/:(.*)/', $event['start'], $event['start']);
        preg_match('/:(.*)/', $event['end'], $event['end']);
        preg_match('/:(.*)/', $event['end1'], $event['end1']);
    
        $event['start'] = $event['start'][1] ?? '';
        $event['end'] = $event['end'][1] ?? '';
        $event['end1'] = $event['end1'][1] ?? '';
    
        return $event;
    }

    /**
     * Event date formatting function
     * @param mixed $event
     * @return void
     */
    private function format_event_dates(mixed &$event): void
    {
        $timezone = strlen($event['start']) < 17;
        if ($timezone) {
            $event['start'] .= "Z";
            $event['end'] .= "Z";
            $event['end1'] .= "Z";
        }
    
        $startYear = substr($event['start'], 0, 4);
        $endYear = substr($event['end'], 0, 4);
        $startMonth = substr($event['start'], 4, 2);
        $endMonth = substr($event['end'], 4, 2);
        $startDay = substr($event['start'], 6, 2);
        $endDay = substr($event['end'], 6, 2);
        $startHour = substr($event['start'], 9, 2);
        $endHour = substr($event['end'], 9, 2);
        $startMinutes = substr($event['start'], 11, 2);
        $endMinutes = substr($event['end'], 11, 2);
    
        if (!$timezone) {
            $startHour = (intval($startHour) + 1) % 24;
            $endHour = (intval($endHour) + 1) % 24;
        }
    
        $event['start'] = sprintf("%02d.%s - %s.%s.%s", $startHour, $startMinutes, $startDay, $startMonth, $startYear);
        $event['end'] = sprintf("%02d.%s - %s.%s.%s", $endHour, $endMinutes, $endDay, $endMonth, $endYear);
    }

    /**
     * iCal event processing function
     * @param DateTime $eventDate
     * @param DateTime $currentDate
     * @return int $days
     */
    private function calculate_days_until_event(DateTime $eventDate, DateTime $currentDate): int
    {
        $interval = $currentDate->diff($eventDate);
        return $interval->days;
    }

    /**
     * Checking if the event is happening in 7 days function
     * @param DateTime $eventDate
     * @param DateTime $currentDate
     * @param int $daysUntilEvent
     * @return bool
     */
    private function should_include_event(DateTime $eventDate, DateTime $currentDate, int $daysUntilEvent): bool
    {
        return $eventDate > $currentDate && $daysUntilEvent <= 7;
    }

    /**
     * Recurring events generating function
     * @param array $event
     * @param array $events
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return void
     * @throws DateMalformedStringException
     */
    public function generateRecurringEvents(array &$events, array $event, DateTime $startDate, DateTime $endDate): void
    {
        $rruleParts = [];

        parse_str(str_replace(';', '&', $event['rrule']), $rruleParts);

        $freq = $rruleParts['FREQ'] ?? '';

        $count = isset($rruleParts['COUNT']) ? (int)$rruleParts['COUNT'] : PHP_INT_MAX;
        $maxDate = new DateTime('2025-06-30');
        
        // Pobiera informację o dniu tygodnia, jeśli istnieje
        $byday = $rruleParts['BYDAY'] ?? '';

        $interval = match ($freq) {
            'DAILY' => '+1 day',
            'WEEKLY' => '+1 week',
            'MONTHLY' => '+1 month',
            default => null,
        };

        if (!$interval) return;

        for ($i = 1; $i < $count; $i++) {
            $startDate->modify($interval);
            $endDate->modify($interval);

            if ($startDate > $maxDate) break;

            $newEvent = $event;
            $newEvent['start'] = $startDate->format('H.i - d.m.Y');
            $newEvent['end'] = $endDate->format('H.i - d.m.Y');

            $events[] = $newEvent;
        }
    }
}

