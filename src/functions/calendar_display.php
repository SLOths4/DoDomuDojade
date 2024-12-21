<div id="schedule">

<?php
include("./utilities/calendar.php");
$calendar = new Calendar();
$events = $calendar->get_events();

// Display the events
foreach ($events as $event) {
    echo "Title: " . htmlspecialchars($event['summary']) . "<br>";
    echo "Start: " . htmlspecialchars($event['start']) . "<br>";
    echo "End: " . htmlspecialchars($event['end']) . "<br>";
    echo "Description: " . htmlspecialchars($event['description']) . "<br><br>";
}
?>

</div>
