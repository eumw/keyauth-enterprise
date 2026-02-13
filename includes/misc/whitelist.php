<?php 

namespace misc\whitelist;

use misc\etc;
use misc\cache;
use misc\mysql;

function addWhite($ip, $secret = null)
{
        $ip = etc\sanitize($ip);

        $query = mysql\query("INSERT INTO `whitelist`(`ip`, `app`) VALUES (?, ?)",[$ip, $secret ?? $_SESSION['app']]);

        cache\purge('KeyAuthWhitelist:' . ($secret ?? $_SESSION['app']) . ':' . $ip);
                        
        if ($query->affected_rows > 0) {
                return 'success';
        } else {
                return 'failure';
        }
}

function deleteWhite($ip, $secret = null)
{
        $ip = etc\sanitize($ip);

        $query = mysql\query("DELETE FROM `whitelist` WHERE `app` = ? AND `ip` = ?",[$secret ?? $_SESSION['app'], $ip]);
        cache\purge('KeyAuthWhitelist:' . ($secret ?? $_SESSION['app']) . ':' . $ip);
                        
        if ($query->affected_rows > 0) {
                return 'success';
        } else {
                return 'failure';
        }
}

function deleteAll($secret = null){
    $query = mysql\query("DELETE FROM `whitelist` WHERE `app` = ?", [$secret ?? $_SESSION['app']]);

    if ($query->affected_rows > 0){
        cache\purgePattern('KeyAuthWhitelist:' . ($secret ?? $_SESSION['app']));
        if ($_SESSION['role'] == "seller" || !is_null($secret)) {
            cache\purge('KeyAuthWhitelist:' . ($secret ?? $_SESSION['app']));
        } 
        return 'success';
    } else {
        return 'failure';
    }
}
