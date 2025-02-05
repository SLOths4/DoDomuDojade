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
        foreach ($matches[1] as $eventData) {
            $event = [];
            preg_match('/SUMMARY:(.*)/', $eventData, $summary);
            preg_match('/DTSTART(.*)/', $eventData, $start);
            preg_match('/DTEND(.*)/', $eventData, $end);
            preg_match('/DESCRIPTION:(.*)/', $eventData, $description);
            preg_match('/RRULE:(.*)/', $eventData, $rrule);

            $event['summary'] = $summary[1] ?? '';
            $event['start'] = $start[1] ?? '';
            $event['end'] = $end[1] ?? '';
            $event['end1'] = $end[1] ?? '';
            $event['description'] = $description[1] ?? '';
            $event['rrule'] = $rrule[1] ?? '';

            preg_match('/:(.*)/', $event['start'], $event['start']);
            preg_match('/:(.*)/', $event['end'], $event['end']);
            preg_match('/:(.*)/', $event['end1'], $event['end1']);

            $event['start'] = $event['start'][1] ?? '';
            $event['end'] = $event['end'][1] ?? '';
            $event['end1'] = $event['end1'][1] ?? '';

            $this->logger->debug("Variable state after preg_matching:\n". $event['start'] . "\n" . $event['end'] . "\n" . $event['end1']);
            if ($event['rrule']) {
                $this->logger->debug("Recurrency rules" . $event['rrule']);
            }
            $timezone = strlen($event['start']) < 17;

            if ($timezone) {
                $event['start'] = trim($event['start']) . "Z";
                $event['end'] = trim($event['end']) . "Z";
                $event['end1'] = trim($event['end1']) . "Z";
            }
            $formatstdate = substr($event['start'], 0, 15);
            $formatenddate = substr($event['end'], 0, 15);
            
            $startyear = substr($event['start'], 0, 4);
            $endyear = substr($event['end'], 0, 4);
            $startmonth = substr($event['start'], 4, 2);
            $endmonth = substr($event['end'], 4, 2);
            $startday = substr($event['start'], 6, 2);
            $endday = substr($event['end'], 6, 2);
            $startfullhour = substr($event['start'], 9, 2);
            $endfullhour = substr($event['end'], 9, 2);
            $startminutes = substr($event['start'], 11, 2);
            $endminutes = substr($event['end'], 11, 2);
            
            if (!$timezone) {
                $startfullhour = (intval($startfullhour) + 1) % 24;
                $endfullhour = (intval($endfullhour) + 1) % 24;
            }

            $event['start'] = sprintf("%02d.%s - %s.%s.%s", $startfullhour, $startminutes, $startday, $startmonth, $startyear);
            $event['end'] = sprintf("%02d.%s - %s.%s.%s", $endfullhour, $endminutes, $endday, $endmonth, $endyear);

            $this->logger->debug("Reconstructed \"start\" and \"end\" variables:\n". $event['start'] . "\n" . $event['end']);


            try {
                $eventDate = substr($event['end1'], 0, 15);
                $eventDate = DateTime::createFromFormat("Ymd\THis", $eventDate);
                if (!$eventDate) {
                    throw new Exception("Invalid date format: $eventDate");
                }
                $currentDate = new DateTime();

            } catch (Exception $e) {
                $this->logger->error("Date parsing failed: " . $e->getMessage() );
                throw new RuntimeException("Date parsing failed: " . $e->getMessage());
            }
            $interval = $currentDate->diff($eventDate); // Get the difference between dates
            $daysUntilEvent = $interval->days; // Extract the number of days

            try {
                $startDate = DateTime::createFromFormat('Ymd\THis', $formatstdate);
                $endDate = DateTime::createFromFormat('Ymd\THis', $formatenddate);
                if (!$startDate || !$endDate) {
                    throw new Exception("Invalid date format");
                }
            } catch (Exception $e) {
                throw new RuntimeException("Date parsing failed: " . $e->getMessage());
            }
            if ($eventDate > $currentDate && $daysUntilEvent <= 7) {
                $events[] = $event;
            }
            if (!empty($event['rrule'])) {
                $this->generateRecurringEvents($events, $event, $startDate, $endDate);
            }
        }


        return $events;
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
    
    public function displaySortedEvents(array $events): void {
        usort($events, function ($a, $b) {
            $dateA = DateTime::createFromFormat('H.i - d.m.Y', $a['start']);
            $dateB = DateTime::createFromFormat('H.i - d.m.Y', $b['start']);
            
            return $dateA <=> $dateB;
        });
        if (!empty($events)) {
            foreach ($events as $event) {
                echo "<div class='calendar-event'>";
                echo "<i class='fa-regular fa-calendar'></i> Wydarzenie: " . htmlspecialchars($event['summary']) . "<br>";
                echo "<i class='fa-solid fa-hourglass-start'></i> Start: " . htmlspecialchars($event['start']) . "<br>";
                echo "<i class='fa-solid fa-hourglass-end'></i> Koniec: " . htmlspecialchars($event['end']) . "<br>";
                if ($event['description'] != "" ) {
                    echo "Opis wydarzenia: " . htmlspecialchars($event['description']) . "<br><br>";
                } else {
                    echo "<br>";
                }
                echo "</div>";
            }
        } else {
            echo "Brak wydarzeń do wyświetlenia.";
        }
    }
}


