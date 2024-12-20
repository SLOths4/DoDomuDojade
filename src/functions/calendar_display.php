<div id="schedule">

<?php
include("./utilities/calendar.php");
$calendar = new Calendar();
$calendar->get_events();
?>
</div>
