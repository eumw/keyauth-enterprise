<script src="https://cdn.keyauth.cc/dashboard/unixtolocal.js"></script>

<div class="w-full max-w-sm  border border-gray-700 rounded-lg shadow">
    <div class="flex justify-end px-4 pt-2">
        <button id="dropdownButton" data-dropdown-toggle="dropdown"
            class="inline-block text-gray-500 hover:opacity-60 focus:ring-0 p-1.5" type="button">
            <span class="sr-only">Open dropdown</span>
            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                viewBox="0 0 16 3">
                <path
                    d="M2 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6.041 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM14 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z" />
            </svg>
        </button>
        <!-- Dropdown menu -->
        <form method="post">
            <div id="dropdown" class="z-10 hidden text-base list-none bg-[#09090d] rounded-lg shadow w-44">
                <ul class="py-2" aria-labelledby="dropdownButton">
                    <li>
                        <a href="?page=logout" class="block px-4 py-2 text-sm text-white hover:bg-blue-700">Log Out</a>
                    </li>
                </ul>
            </div>
        </form>
    </div>
    <div class="flex flex-col items-center pb-4">
        <?php
                        $query = misc\mysql\query("SELECT * FROM `subs` WHERE `app` = ? AND `user` = ? AND `expiry` > ? LIMIT 1", [$_SESSION['panelapp'], $_SESSION['un'], time()]);
                        $row = mysqli_fetch_array($query->result);
                        $subName = $row["subscription"];
                        $subExpiry = $row["expiry"];
                        ?>
        <h5 class="mb-1 text-xl font-medium text-blue-700 stars"><?= $_SESSION["un"]; ?></h5>

        <?php
        $cssClasses = "text-transparent bg-clip-text bg-gradient-to-r to-blue-600 from-sky-400 text-xs font-black mr-2 px-1.5 py-0.5 rounded border mb-2 mt-2";
        $cssClasses .= " border-white-400";

        // Finally, output the HTML with the calculated classes
        echo "<p class=\"$cssClasses\">" . $subName . "</p>";

        ?>
        <label class="text-sm text-gray-400"><b>Expires:</b>
            <script>
            document.write(convertTimestamp(<?= $subExpiry; ?>));
            </script>
        </label>
    </div>
</div>