<?php

namespace misc\auditLog;
use misc\mysql;
use misc\cache;
use dashboard\primary;

function send($event){
        $query = mysql\query("SELECT `auditLogWebhook` FROM `apps` WHERE `secret` = ?",[$_SESSION['app']]);

        $row = mysqli_fetch_array($query->result);
        if(!is_null($row['auditLogWebhook'])) {
                primary\wh_log($row['auditLogWebhook'], "**User:** " . $_SESSION['username'] . "**Event:** $event", "", "3586ee");
        }
        else {
                $query = mysql\query("INSERT INTO `auditLog` (`user`, `event`, `time`, `app`) VALUES (?, ?, ?, ?)",[$_SESSION['username'], $event, time(), $_SESSION['app']]);
        }
}
function deleteAll($secret = null){
        $query = mysql\query("DELETE FROM `auditLog` WHERE `app` = ?",[$secret ?? $_SESSION['app']]);
        if ($query->affected_rows > 0){
        if ($_SESSION['role'] == "seller" || !is_null($secret)){
                cache\purge('KeyAuthAuditLogs:' . ($secret ?? $_SESSION['app']));
        } return 'success'; 
        } else {
                return 'failure';
        }
}