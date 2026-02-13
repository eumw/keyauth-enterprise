<?php
require '../../includes/misc/autoload.phtml';

ob_start();

require '../../includes/dashboard/autoload.phtml';
require '../../includes/api/1.0/autoload.phtml';
require '../../includes/api/shared/autoload.phtml';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['panelapp'])) {
    die("You must go to your panel login first then visit this page");
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
?>

<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <base href="">
    <title>Register</title>
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
                    Register</h1>
                <p class="mb-6 text-lg font-normal text-gray-500 lg:text-xl">Please register an account for the panel you are trying to access.
                    This page is <b>NOT</b> the KeyAuth register page. If you do not know how you got here, or if you were not given permission to access
                    this panel, please exit the page now.
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
                        <div class="relative">
                            <input type="text" name="license"
                                class="block px-2.5 pb-2.5 pt-4 w-full text-sm text-white-900 bg-transparent rounded-lg border-1 border-gray-700 appearance-none focus:outline-none focus:ring-0 focus:border-blue-600 peer"
                                placeholder=" " autocomplete="on" required />
                            <label for="license"
                                class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-[#09090d] px-2 peer-focus:px-2 peer-focus:text-blue-600 peer-focus:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">License</label>
                        </div>
                        <button name="register"
                            class="w-full px-5 py-3 text-base font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 sm:w-full">Register
                            </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!--Flowbite JS-->
    <script src="https://cdn.keyauth.cc/v3/dist/flowbite.js"></script>

    <?php
    if (isset($_POST['register'])) {
        $username = misc\etc\sanitize($_POST['username']);
        $password = misc\etc\sanitize($_POST['password']);
        $license = misc\etc\sanitize($_POST['license']);
        
        // first NULL is email, second NULL is hwid
        $resp = api\v1_0\register($username, $license, $password, NULL, NULL, $_SESSION['panelapp']);
        switch ($resp) {
            case 'username_taken':
                dashboard\primary\error("Username taken!");
                break;
            case 'key_not_found':
                dashboard\primary\error("License doesn't exist!");
                break;
            case 'un_too_short':
                dashboard\primary\error("Username too short, try longer one.");
                break;
            case 'key_already_used':
                dashboard\primary\error("License already used!");
                break;
            case 'pw_leaked':
                dashboard\primary\error("This password has been leaked in a data breach (not from us), please use a different one.");
                break;
            case 'key_banned':
                dashboard\primary\error("License is banned!");
                break;
            default:
                $_SESSION['un'] = $username;
                header("location: ../dashboard/");
                break;
        }
    }
    ?>
</body>

</html>