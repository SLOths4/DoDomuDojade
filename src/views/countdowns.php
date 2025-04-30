<?php
namespace src\views;

require_once __DIR__ . '/../../vendor/autoload.php';

use src\core\SessionHelper;

SessionHelper::start();
$error = SessionHelper::get('error');
SessionHelper::remove('error');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Panel | DoDomuDojadę</title>
    <link rel="icon" type="image/x-icon" href="assets/resources/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link href="/assets/styles/output.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <script src="https://kit.fontawesome.com/d85f6b75e6.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/flowbite@1.6.5/dist/flowbite.min.js"></script>
</head>
<body>
<?php include('functions/navbar.php'); ?>

<form method="POST" action="panel/add_coutdown">
    <div>
        <label>
            <input type="text" name="title" placeholder="Title" class="w-full p-2 border rounded" required>
        </label>
    </div>
    <div>
        <label>
            <input type="date" name="count_to" placeholder="Count to" class="w-full p-2 border rounded" required>
        </label>
    </div>
    <input type="submit" name="add_coundtdown" value="Add">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(SessionHelper::get('csrf_token')) ?>">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars(SessionHelper::get('user_id')) ?>">
</form>

<!-- IMPORT FOOTER -->
<?php include('functions/footer.php'); ?>
</body>
    </html><?php
