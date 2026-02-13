<?php 
$page = isset($_GET['page']) ? $_GET['page'] : "customer-actions";
require '../../app/layout/topbar.php';
?>

<div class="flex overflow-hidden pt-16 bg-[#09090d]">

<?php 
include '/usr/share/nginx/html/panel/dashboard/layout/customeraside.php';
?>

<div class="hidden fixed inset-0 z-10" id="sidebarBackdrop"></div>

<div id="main-content" class="overflow-y-auto relative w-full h-full lg:ml-64">
        <main>
        <?php
        try {
            if (str_contains($page, ".")) {
                throw new ErrorException('');
            }
            else {
                $filePath = __DIR__ . "/../pages/{$page}.php";

                if (file_exists($filePath)) {
                    require $filePath;
                }
                else {
                    throw new ErrorException('');
                }
            }
        } catch (ErrorException $ex) {
            require '../../404_error.html';
        }
        ?>
        </main>

        <?php 
    require '../../app/layout/footer.php';
    ?>
    </div>
</div>