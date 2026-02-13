<?php

namespace misc\sub;

use misc\etc;
use misc\cache;
use misc\mysql;

function deleteSingular($subscription, $secret = null){
        $subscription = etc\sanitize($subscription);

        $query = mysql\query("DELETE FROM `subscriptions` WHERE `app` = ? AND `name` = ?",[$secret ?? $_SESSION['app'], $subscription]);
        if ($query->affected_rows > 0) {
                if ($_SESSION['role'] == "seller" || !is_null($secret)) {
                        cache\purge('KeyAuthSubscriptions:' . ($secret ?? $_SESSION['app']));
                }
                return 'success';
        } else {
                return 'failure';
        }
}
function deleteAll($secret = null){
        $query = mysql\query("DELETE FROM `subscriptions` WHERE `app` = ? AND `name` != ?",[$secret ?? $_SESSION['app'], "default"]);
        if ($query->affected_rows > 0) {
                if ($_SESSION['role'] == "seller" || !is_null($secret)) {
                        cache\purge('KeyAuthSubscriptions:' . ($secret ?? $_SESSION['app']));
                }
                return 'success';
        } else {
                return 'failure';
        }
}
function add($name, $level, $secret = null){
        $name = etc\sanitize($name);
        $level = etc\sanitize($level);

        $query = mysql\query("SELECT 1 FROM `subscriptions` WHERE `name` = ? AND `app` = ?", [$name, $secret ?? $_SESSION['app']]);

        if ($query->num_rows > 0){
                return 'already_exists';
        }

        $query = mysql\query("INSERT INTO `subscriptions` (`name`, `level`, `app`) VALUES (?, ?, ?)",[$name , $level,$secret ?? $_SESSION['app']]);
        if ($query->affected_rows > 0) {
                if ($_SESSION['role'] == "seller" || !is_null($secret)) {
                        cache\purge('KeyAuthSubscriptions:' . ($secret ?? $_SESSION['app']));
                }
                return 'success';
        } else {
                return 'failure';
        }
}
function pause($subscription, $secret = null){
        $subscription = etc\sanitize($subscription);
        $query = mysql\query("SELECT * FROM `subs` WHERE `app` = ? AND `expiry` > ? AND `subscription` = ?", [$secret ?? $_SESSION['app'], time(), $subscription], "sis");
        $updateQuery = NULL;
        while ($row = mysqli_fetch_array($query->result)){
                $expires = $row['expiry'];
                $exp = (int)$expires - time();
                $updateQuery = mysql\query("UPDATE `subs` SET `paused` = 1, `expiry` = ? WHERE `app` = ? AND `id` = ?", [$exp, $secret ?? $_SESSION['app'], $row['id']], "iss");
        }
        if ($updateQuery->affected_rows > 0){
                cache\purge('KeyAuthSubs:' . $secret ?? $_SESSION['app'] . ':' . $subscription);
                return 'success';
        } else {
                return 'failure';
        }
}
function unpause($subscription, $secret = null){
        $subscription = etc\sanitize($subscription);
        $query = mysql\query("SELECT * FROM `subs` WHERE `app` = ? AND `subscription` = ? AND `paused` = 1", [$secret ?? $_SESSION['app'], $subscription]);
        $updateQuery = NULL;
        while ($row = mysqli_fetch_array($query->result)){
                $expires = $row['expiry'];
                $exp = (int)$expires + time();
                $updateQuery = mysql\query("UPDATE `subs` SET `paused` = 0, `expiry` = ? WHERE `app` = ? AND `id` = ?", [$exp, $secret ?? $_SESSION['app'], $row['id']], "iss");
        }
        if ($updateQuery->affected_rows > 0){
                cache\purge('KeyAuthSubs:' . $secret ?? $_SESSION['app'] . ':' . $subscription);
                return 'success';
        } else {
                return 'failure';
        }
}