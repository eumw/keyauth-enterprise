<?php
require '../../includes/misc/autoload.phtml';
require '../../includes/dashboard/autoload.phtml';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['un'])) {
    die("Not logged in");
}

set_exception_handler(function ($exception) {
    error_log("\n--------------------------------------------------------------\n");
    error_log($exception);
    error_log("\nRequest data:");
    error_log(print_r($_POST, true));
    error_log("\n--------------------------------------------------------------");
    http_response_code(500);
    global $databaseUsername;
    $errorMsg = str_replace($databaseUsername, "REDACTED", $exception->getMessage());
    \dashboard\primary\error($errorMsg);
});

$query = misc\mysql\query("SELECT `name`, `download`, `webdownload`, `cooldown`, `customerPanelIcon` FROM `apps` WHERE `secret` = ?", [$_SESSION['panelapp']]);
$row = mysqli_fetch_array($query->result);

$name = $row["name"];
$download = $row["download"];
$webdownload = $row["webdownload"];
$appcooldown = $row["cooldown"];
$customerPanelIcon = $row["customerPanelIcon"];

?>
<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden dark">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/github-dark.min.css">
    <script src="https://cdn.keyauth.cc/v3/highlight.min.js"></script>
    <script src="https://cdn.keyauth.cc/dashboard/unixtolocal.js"></script>

    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="shortcut icon" href="<?= $customerPanelIcon ?>" />
    <title><?= $name; ?> Panel</title>

    <script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>

    <style>
    body {
        margin: 0;
        padding: 0;
        overflow: hidden;
    }

    #loader {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: black;
        /* Black background color for the loader */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 9999;
    }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/flowbite@1.8.1/dist/flowbite.min.css" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <script src="https://cdn.tailwindcss.com/3.3.3"></script>

    <script src="https://unpkg.com/flowbite@1.8.1/dist/flowbite.js"></script>

    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
</head>

<body>

    <?php include 'layout/master.php' ?>
</body>

</html>