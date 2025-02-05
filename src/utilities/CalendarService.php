<?php
namespace src\utilities;

use DateTime;
use Exception;
use RuntimeException;
use Monolog\Logger;

/**
 * iCal data parsing and customising class
 * @author Igor Woźnica <igor.supermemo@gmail.com>
 * @version 1.0.1
 * @since 1.0.0
 */

class CalendarService {
    private string $icalUrl;
    private Logger $logger;

    public function __construct(Logger $logger) {
        $config = require 'config.php';
        $this->logger = $logger;
        $this->icalUrl = $config['Calendar'][0]['url'];
    }

    public function get_events() {
        // Fetch the iCal data
        $icalData = @file_get_contents($this->icalUrl);

        if ($icalData === false) {
            $error = error_get_last();
            echo "Error fetching iCal data: " . $error['message'];
            throw new \Exception("Error fetching iCal data: " . $error['message']);
            $this->logger->error("Error fetching iCal data: " . $error['message']);
        } else {
            $this->logger->debug("Successfully fetched the iCal data");
        }
      
        if (strpos($icalData, 'BEGIN:VEVENT') === false) {
            echo "No events found in the iCal data.";
            $this->logger->debug("No events found in the iCal data.");
            return [];
        } else {
            $this->logger->debug("Found events in the iCal data.");
        }

        // Parse the iCal data

        return $this->parse_ical_data($icalData);
    }

    private function parse_ical_data($icalData) {
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
    
    private function process_event($eventData, &$events, $currentDate) {
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
            $this->logger->error("Date parsing failed: " . $e->getMessage());
        }
    }
    
    private function extract_event_data($eventData) {
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
    
    private function format_event_dates(&$event) {
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
    
    private function calculate_days_until_event($eventDate, $currentDate) {
        $interval = $currentDate->diff($eventDate);
        return $interval->days;
    }
    
    private function should_include_event($eventDate, $currentDate, $daysUntilEvent) {
        return $eventDate > $currentDate && $daysUntilEvent <= 7;
    }
    

    public function generateRecurringEvents(&$events, $event, $startDate, $endDate) {
        $rruleParts = [];
        
        // Konwertuje RRULE na tablicę wartości
        parse_str(str_replace(';', '&', $event['rrule']), $rruleParts);
        
        // Pobiera częstotliwość powtarzania (DAILY, WEEKLY, MONTHLY)
        $freq = $rruleParts['FREQ'] ?? '';
        
        // Pobiera liczbę powtórzeń, jeśli nie podano COUNT, ustawiamy datę maksymalną
        $count = isset($rruleParts['COUNT']) ? (int)$rruleParts['COUNT'] : PHP_INT_MAX;
        $maxDate = new DateTime('2025-06-30');
        
        // Pobiera informację o dniu tygodnia, jeśli istnieje
        $byday = $rruleParts['BYDAY'] ?? '';
        
        // Ustalanie interwału na podstawie częstotliwości
        $interval = match ($freq) {
            'DAILY' => '+1 day',
            'WEEKLY' => '+1 week',
            'MONTHLY' => '+1 month',
            default => null,
        };
        
        // Jeśli brak interwału, zakończ
        if (!$interval) return;
        
        // Tworzenie powtarzających się wydarzeń
        for ($i = 1; $i < $count; $i++) {
            // Zmienia datę rozpoczęcia i zakończenia zgodnie z interwałem
            $startDate->modify($interval);
            $endDate->modify($interval);
            
            // Sprawdza, czy nowe wydarzenie nie przekracza maksymalnej daty
            if ($startDate > $maxDate) break;
            
            // Tworzy nowe wydarzenie na podstawie oryginalnego
            $newEvent = $event;
            $newEvent['start'] = $startDate->format('H.i - d.m.Y');
            $newEvent['end'] = $endDate->format('H.i - d.m.Y');
            
            // Dodaje nowe wydarzenie do tablicy
            $events[] = $newEvent;
        }
    }
}

