<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['un']);
unset($_SESSION['panelapp']);

$oldUrl = $_SESSION['oldUrlPanel'];
if(isset($oldUrl))
        echo "<meta http-equiv='Refresh' Content='0; url=$oldUrl'>"; 
else
        die('<br><p style="font-size:25px">Logged Out!</p>');
