<?php
if (isset($_POST['resethwid'])) {
    $today = time();
    $cooldown = $today + $appcooldown;

    misc\mysql\query("UPDATE `users` SET `hwid` = NULL, `cooldown` = ? WHERE `app` = ? AND `username` = ?", [$cooldown, $_SESSION['panelapp'], $_SESSION['un']]);

    dashboard\primary\success("Reset HWID!");
    misc\cache\purge('KeyAuthUser:' . $_SESSION['panelapp'] . ':' . $_SESSION['un']);
    echo "<meta http-equiv='Refresh' Content='2;'>";
}
if (isset($_POST['saveUser'])) {
    if (!empty($_POST['username'])) {
            $resp = misc\user\changeUsername($_SESSION['un'], $_POST['username'], $_SESSION['panelapp']);
            match($resp){
                    'already_used' => dashboard\primary\error("Username already used!"),
                    'failure' => dashboard\primary\error("Failed to change username!"),
                    'success' => dashboard\primary\success("Successfully changed username!"),
                    default => dashboard\primary\error("Unhandled Error! Contact us if you need help")
            };
    }
    if (!empty($_POST['password'])) {
            $resp = misc\user\changePassword($_SESSION['un'], $_POST['password'], $_SESSION['panelapp']);
            match($resp){
                    'failure' => dashboard\primary\error("Failed to change password!"),
                    'success' => dashboard\primary\success("Successfully changed password!"),
                    default => dashboard\primary\error("Unhandled Error! Contact us if you need help")
            };
    }
    if (empty($_POST['username']) && empty($_POST['password'])) {
            dashboard\primary\error("You must enter new username or password!");
    }
}
?>
<div class="p-4 bg-[#09090d] block sm:flex items-center justify-between lg:mt-1.5">
    <div class="mb-1 w-full bg-[#0f0f17] rounded-xl">
        <div class="mb-4 p-4">
            <h1 class="text-xl font-semibold text-white-900 sm:text-2xl">Customer Actions</h1>
            <p class="text-xs text-gray-500">View and Modify your account on <?= ucwords($page); ?></p>
            <div class="pt-5">
                <form method="POST">
                    <div class="relative mb-4">
                        <input type="text" id="username" name="username"
                            class="block px-2.5 pb-2.5 pt-4 w-full text-sm text-white bg-transparent rounded-lg border-1 border-border-gray-300 appearance-none focus:ring-0 peer"
                            placeholder=" " autocomplete="on">
                        <label for="username"
                            class="absolute text-sm text-gray-400 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-[#0f0f17] px-2 peer-focus:px-2 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">New
                            Username:</label>
                    </div>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                            class="block px-2.5 pb-2.5 pt-4 w-full text-sm text-white bg-transparent rounded-lg border-1 border-border-gray-300 appearance-none focus:ring-0 peer"
                            placeholder=" " autocomplete="on">
                        <label for="password"
                            class="absolute text-sm text-gray-400 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-[#0f0f17] px-2 peer-focus:px-2 peer-focus:text-blue-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">New
                            Password:</label>
                    </div>
                    <button name="saveUser"
                        class="text-white border-2 hover:bg-white hover:text-black focus:ring-0 focus:outline-none transition duration-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center items-center mb-3 w-full mt-5">
                        <span class="inline-flex">
                            Update Changes
                            <svg class="w-3.5 h-3.5 ml-2 mt-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 14 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"></path>
                            </svg></span>
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-[#09090d] block sm:flex items-center justify-between lg:mt-5">
            <div class="mb-1 w-full bg-[#0f0f17] mt-4 md:mt-2 rounded-xl">
                <div class="mb-4 p-4">
                    <h1 class="text-xl font-semibold text-white-900 sm:text-2xl">HWID Reset</h1>
                    <p class="text-xs text-gray-500">Reset your hwid</p>
                    <div class="pt-5">
                        <?php
                     $query = misc\mysql\query("SELECT * FROM `users` WHERE `app` = ? AND `username` = ?", [$_SESSION['panelapp'], $_SESSION['un']]);
                     $row = mysqli_fetch_array($query->result);
                     $today = time();
                     $cooldown = $row["cooldown"];

                     if (is_null($cooldown)){
                        echo '<form method="post"><button
                        class="inline-flex text-white bg-blue-700 hover:opacity-60 focus:ring-0 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 transition duration-200"
                        name="resethwid">
                        <i class="lni lni-reload mr-2 mt-1"></i>Reset HWID
                    </button></form>';
                     } else {
                        if ($today > $cooldown) {
                            echo '<form method="post"><button
                            class="inline-flex text-white bg-blue-700 hover:opacity-60 focus:ring-0 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 transition duration-200"
                            name="resethwid">
                            <i class="lni lni-reload mr-2 mt-1"></i>Reset HWID
                        </button></form>';
                        } else {
                            echo '<button
                            class="inline-flex text-white bg-red-700 hover:opacity-60 focus:ring-0 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 transition duration-200">
                            <i class="lni lni-reload mr-2 mt-1"></i>HWID cooldown until <script>document.write(convertTimestamp(' . $cooldown . '));</script>
                        </button>';
                        }
                     }
?>
                    </div>
                </div>

                <?php 
                                if (!is_null($webdownload)) {
                                    $query = misc\mysql\query("SELECT `password` FROM `users` WHERE `username` = ? AND `app` = ?", [$_SESSION['un'], $_SESSION['panelapp']]);
                                    $row = mysqli_fetch_array($query->result);

                                    $token = md5(substr($row["password"], -5));
                                ?>
                <div class="bg-[#09090d] block sm:flex items-center justify-between lg:mt-5">
                    <div class="mb-1 w-full bg-[#0f0f17] mt-4 md:mt-2 rounded-xl">
                        <div class="mb-4 p-4">
                            <h1 class="text-xl font-semibold text-white-900 sm:text-2xl">Web Loader</h1>
                            <p class="text-xs text-gray-500">Control your application from the website</p>
                            <div class="pt-5" id="buttons">
                                <?php
                                    $query = misc\mysql\query("SELECT * FROM `buttons` WHERE `app` = ?", [$_SESSION['panelapp']]);
                                    $rows = array();
                                    while ($r = mysqli_fetch_assoc($query->result)) {
                                        $rows[] = $r;
                                    }

                                    foreach ($rows as $row) {
                                    ?>
                                <button
                                    class="inline-flex text-white bg-green-700 hover:opacity-60 focus:ring-0 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 transition duration-200"
                                    onclick="doButton(this.value)"
                                    value="<?= $row['value']; ?>"><?= $row['text']; ?></button>
                                <?php } ?>
                            </div>
                            <a href="<?= $webdownload; ?>" class="text-blue-700">
                                <button onclick="handshake()"
                                    class="inline-flex text-white bg-blue-700 hover:opacity-60 focus:ring-0 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 transition duration-200">
                                    <i class="lni lni-download mr-2 mt-1"></i>Download
                                </button>
                            </a>
                        </div>
                        <?php } ?>

                        <div class="bg-[#09090d] block sm:flex items-center justify-between lg:mt-5">
                            <div class="mb-1 w-full bg-[#0f0f17] mt-4 md:mt-2 rounded-xl">
                                <div class="mb-4 p-4">
                                    <h1 class="text-xl font-semibold text-white-900 sm:text-2xl">Stay Updated!
                                    </h1>
                                    <p class="text-xs text-gray-500">If there is a link visible, there is a new version
                                        of the app
                                        <?= $name; ?></p>
                                    <div class="pt-5">
                                        <?php 
                                            if (!is_null($download)){ ?>
                                        <a href="<?= $download; ?>" class="text-blue-700" target="appdownload">
                                            <button
                                                class="inline-flex text-white bg-blue-700 hover:opacity-60 focus:ring-0 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 transition duration-200">
                                                <i class="lni lni-download mr-2 mt-1"></i>Download new version of
                                                <?= $name; ?>
                                            </button>
                                        </a>
                                        <?php } else {?> 
                                            <button
                                                class="inline-flex text-white bg-red-700 hover:opacity-60 focus:ring-0 font-medium rounded-lg text-sm px-5 py-2.5 mb-2 transition duration-200">
                                                <i class="lni lni-checkmark mr-2 mt-1"></i>You're on the current version!
                                            </button>
                                        <?php } ?>
                                    </div>
                                </div>

                                <script>
                                var going = 1;

                                function handshake() {
                                    if (navigator.userAgent.includes("Chrome/98")) {
                                        going = 0;
                                        alert(
                                            "Currently Chrome 98 does not work with the web loader. Please use an older chrome version such as Chrome 97 or use another browser such as Firefox"
                                        );
                                    }
                                    setTimeout(function() {
                                        var xmlHttp = new XMLHttpRequest();
                                        xmlHttp.open("GET",
                                            "http://localhost:1337/handshake?user=<?= $_SESSION['un']; ?>&token=<?= $token; ?>"
                                        );
                                        xmlHttp.onload = function() {
                                            going = 0;
                                            switch (xmlHttp.status) {
                                                case 420:
                                                    console.log("returned handshake :)");
                                                    $("#handshake").fadeOut(100);
                                                    $("#buttons").fadeIn(1900);
                                                    break;
                                                default:
                                                    alert(xmlHttp.statusText);
                                                    break;
                                            }
                                        };
                                        xmlHttp.send();
                                        if (going == 1) {
                                            handshake();
                                        }
                                    }, 3000);
                                }

                                function doButton(value) {
                                    var xmlHttp = new XMLHttpRequest();
                                    xmlHttp.open("GET", "http://localhost:1337/" + value);
                                    xmlHttp.send();
                                }
                                </script>

                                <script src="https://cdn.keyauth.cc/dashboard/unixtolocal.js"></script>