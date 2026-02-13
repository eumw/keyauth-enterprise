<?php
ob_start();

require '../includes/misc/autoload.phtml';
require '../includes/api/1.0/autoload.phtml';
require '../includes/api/shared/autoload.phtml';
require '../includes/dashboard/autoload.phtml';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['oldUrlPanel'] = $_SERVER['REQUEST_URI'];

if (isset($_SESSION['un'])) {
    header("Location: ../dashboard/");
    exit();
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

$hostname = ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']);
if ($hostname == "keyauth.win" || $hostname == "keyauth.cc") { // custom domain

    $requrl = $_SERVER['REQUEST_URI'];

    $uri = trim($_SERVER['REQUEST_URI'], '/');
    $pieces = explode('/', $uri);
    $owner = urldecode(misc\etc\sanitize($pieces[1]));
    $name = urldecode(misc\etc\sanitize($pieces[2]));

    if (substr_count($requrl, '/') >= 4) {
        header("location: https://keyauth.cc/panel/{$owner}/{$name}");
    }

    $query = misc\mysql\query("SELECT `secret`, `panelstatus`, `paused`, `owner`, `name`, `customerPanelIcon` FROM `apps` WHERE `name` = ? AND `owner` = ?", [$name, $owner]);
} else {
    $query = misc\mysql\query("SELECT `secret`, `panelstatus`, `paused`, `owner`, `name`, `customerPanelIcon` FROM `apps` WHERE `customDomain` = ?",[$hostname]);
}

if ($query->num_rows < 1) {
    die("Panel does not exist.");
}

while ($row = mysqli_fetch_array($query->result)) {
    $secret = $row['secret'];
    $_SESSION['panelapp'] = $secret;
    $panelStatus = $row['panelstatus'];
    $paused = $row['paused'];
    $owner = $row['owner']; // in the cases where custom domain is used
    $name = $row['name']; // in the cases where custom domain is used
    $customerPanelIcon = $row['customerPanelIcon'];
}

if (!$panelStatus) {
    die("Panel was disabled by the application owner");
}

if($paused) {
    die("Program has been paused by the program developer, likely to undergo maintenance updates. Please wait until later.");
}


$query = misc\mysql\query("SELECT 1 FROM `accounts` WHERE `username` = ? AND `role` = 'seller'",[$owner]);

if ($query->num_rows < 1) {
    die("Tell the application owner they need to upgrade to seller to utilize customer panel!");
}

?>

<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <base href="">
    <title>Login to <?= $name; ?> panel</title>
    <link rel="shortcut icon" href="<?= $customerPanelIcon ?>" />
    <!--Custom Styles-->
    <link rel="stylesheet" type="text/css" href="https://cdn.keyauth.cc/v3/dist/output.css" />

    <script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</head>

<body>
    <!--Navbar-->
    <header>
        <nav class="border-gray-200 px-4 lg:px-6 py-2.5 mb-14">
            <div class="flex flex-wrap justify-between items-center mx-auto max-w-screen-xl">
                <a href="../" class="flex items-center">
                    <img src="https://cdn.keyauth.cc/v2/assets/media/logos/logo-1-dark.png" class="mr-3 h-12 mt-2"
                        alt="KeyAuth Logo" />
                </a>
            </div>
        </nav>
    </header>

    <section>
        <div class="py-8 px-4 mx-auto max-w-screen-xl lg:py-16 grid lg:grid-cols-2 gap-8 lg:gap-16">
            <div class="flex flex-col justify-center">
                <h1
                    class="mb-4 text-4xl font-extrabold tracking-tight leading-none text-white-900 md:text-5xl lg:text-6xl">
                    <?= $name; ?> Customer Panel</h1>
                <p class="mb-6 text-lg font-normal text-gray-500 lg:text-xl">To access <a
                        class="text-blue-600"><?= $name; ?> customer panel</a>,
                    you must login, or register. This information is provided to you by the owner of the application. Do
                    not contact KeyAuth support if
                    you can not access the customer panel.
                </p>
            </div>
            <div>
                <div class="w-full lg:max-w-xl p-6 space-y-8 sm:p-8 bg-[#09090d] rounded-lg shadow-xl">
                    <form class="mt-8 space-y-6" method="post">
                        <div class="relative">
                            <input type="text" name="username"
                                class="block px-2.5 pb-2.5 pt-4 w-full text-sm text-white-900 bg-transparent rounded-lg border-1 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                placeholder=" " autocomplete="on" required />
                            <label for="username"
                                class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-[#09090d] px-2 peer-focus:px-2 peer-focus:text-blue-600 peer-focus:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">Username</label>
                        </div>
                        <div class="relative">
                            <input type="password" name="password"
                                class="block px-2.5 pb-2.5 pt-4 w-full text-sm text-white-900 bg-transparent rounded-lg border-1 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                placeholder=" " autocomplete="on" required />
                            <label for="password"
                                class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-[#09090d] px-2 peer-focus:px-2 peer-focus:text-blue-600 peer-focus:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">Password</label>
                        </div>
                        <button name="login"
                            class="w-full px-5 py-3 text-base font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 sm:w-full">Login
                            To <?= $name; ?> Customer Panel</button>
                        <div class="text-sm font-medium text-white-900">
                            New to the panel? <a href="../register/" class="text-blue-600 hover:underline">Register Now</a>.
                        </div>
                        <div class="text-sm font-medium text-white-900">
                            Need to to upgrade your account? <a href="../upgrade/" class="text-blue-600 hover:underline">Upgrade
                                Now</a>.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!--Flowbite JS-->
    <script src="https://cdn.keyauth.cc/v3/dist/flowbite.js"></script>

    <?php
    if (isset($_POST['login'])) {
        if (empty($_POST['username']) || empty($_POST['password'])) {
            dashboard\primary\error("You must fill in all the fields!");
            return;
        }

        $un = misc\etc\sanitize($_POST['username']);
        $password = misc\etc\sanitize($_POST['password']);

        $resp = api\v1_0\login($un, $password, NULL, $secret, 0);
        switch ($resp) {
            case 'un_not_found':
                dashboard\primary\error("User not found!");
                break;
            case 'pw_mismatch':
                dashboard\primary\error("Password is invalid!");
                break;
            case 'user_banned':
                if (strpos($userbanned, '{reason}') !== false) {
                    $query = misc\mysql\query("SELECT `banned` FROM `users` WHERE `app` = ? AND `username` = ?", [$secret, $un]);
                    $row = mysqli_fetch_array($query->result);
                    $reason = $row['banned'];
                }
                dashboard\primary\error("Banned: Reason: " . misc\etc\sanitize($reason));
                break;
            case 'sub_paused':
                dashboard\primary\error("Your subscription is paused! Wait for the developer to unpause it");
                break;
            case 'no_active_subs':
                dashboard\primary\error("You have no active subscriptions! You you change this by clicking upgrade");
                break;
            default:
                $_SESSION['un'] = $un;
                header("location: ../dashboard/");
                break;
        }
    }
    ?>
</body>

</html>