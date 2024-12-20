<?php
class Calendar {
    private $icalUrl;

    public function __construct() {
        $config = json_decode(file_get_contents("./config.json"));
        $this->icalUrl = $config->Calendar[0]->url;
    }

    public function get_events() {
        // Fetch the iCal data
        $icalData = file_get_contents($this->icalUrl);

        if ($icalData === false) {
            throw new Exception("Error fetching iCal data.");
        }

        // Parse the iCal data
        $events = $this->parse_ical_data($icalData);

        // Display the events
        $this->display_events($events);
    }

    private function parse_ical_data($icalData) {
        $events = [];
        preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $icalData, $matches);

        foreach ($matches[1] as $eventData) {
            $event = [];
            preg_match('/SUMMARY:(.*)/', $eventData, $summary);
            preg_match('/DTSTART:(.*)/', $eventData, $start);
            preg_match('/DTEND:(.*)/', $eventData, $end);
            preg_match('/DESCRIPTION:(.*)/', $eventData, $description);

            $event['summary'] = $summary[1] ?? '';
            $event['start'] = $start[1] ?? '';
            $event['end'] = $end[1] ?? '';
            $event['description'] = $description[1] ?? '';

            $events[] = $event;
        }

        return $events;
    }

    private function display_events($events) {
        foreach ($events as $event) {
            echo "Title: " . htmlspecialchars($event['summary']) . "<br>";
            echo "Start: " . htmlspecialchars($event['start']) . "<br>";
            echo "End: " . htmlspecialchars($event['end']) . "<br>";
            echo "Description: " . htmlspecialchars($event['description']) . "<br><br>";
        }
    }
}

?>
