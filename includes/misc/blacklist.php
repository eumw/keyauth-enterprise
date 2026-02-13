<?php

namespace misc\blacklist;

use misc\etc;
use misc\cache;
use misc\mysql;

function add($data, $type, $secret = null)
{
        $data = etc\sanitize($data);
        $type = etc\sanitize($type);

        switch ($type) {
                case 'IP Address':
                        $query = mysql\query("INSERT INTO `bans`(`ip`, `type`, `app`) VALUES (?, 'ip', ?)",[$data, $secret ?? $_SESSION['app'] ]);
                        cache\purgePattern('KeyAuthBlacklist:' . ($secret ?? $_SESSION['app']) . ':' . $data);
                        break;
                case 'Hardware ID':
                        $query = mysql\query("INSERT INTO `bans`(`hwid`, `type`, `app`) VALUES (?, 'hwid', ?)",[$data, $secret ?? $_SESSION['app']]);

                        cache\purgePattern('KeyAuthBlacklist:' . ($secret ?? $_SESSION['app']) . ':*:' . $data);
                        break;
                case 'region':
                        $query = mysql\query("INSERT INTO `bans`(`region`, `type`, `app`) VALUES (?, 'region', ?)",[$data, $secret ?? $_SESSION['app']]);
                        break;
                case 'country':
                        $query = mysql\query("INSERT INTO `bans`(`country`, `type`, `app`) VALUES (?, 'country', ?)",[$data, $secret ?? $_SESSION['app']]);
                        break;
                case 'asn':
                        $query = mysql\query("INSERT INTO `bans`(`asn`, `type`, `app`) VALUES (?, 'asn', ?)",[$data, $secret ?? $_SESSION['app']]);
                        break;
                default:
                        return 'invalid';
        }
        if ($query->affected_rows > 0) {
                if ($_SESSION['role'] == "seller" || !is_null($secret)) {
                        cache\purge('KeyAuthBlacks:' . ($secret ?? $_SESSION['app']));
                }
                return 'success';
        } else {
                return 'failure';
        }
}
function deleteAll($secret = null)
{
        $query = mysql\query("DELETE FROM `bans` WHERE `app` = ?",[$secret ?? $_SESSION['app']]);

        if ($query->affected_rows > 0) {
                cache\purgePattern('KeyAuthBlacklist:' . ($secret ?? $_SESSION['app']));
                if ($_SESSION['role'] == "seller" || !is_null($secret)) {
                        cache\purge('KeyAuthBlacks:' . ($secret ?? $_SESSION['app']));
                }
                return 'success';
        } else {
                return 'failure';
        }
}
function deleteSingular($blacklist, $type, $secret = null)
{
        $blacklist = etc\sanitize($blacklist);
        $type = etc\sanitize($type);

        switch ($type) {
                case 'ip':
                        $query = mysql\query("DELETE FROM `bans` WHERE `app` = ? AND `ip` = ?",[$secret ?? $_SESSION['app'], $blacklist]);
                        cache\purgePattern('KeyAuthBlacklist:' . ($secret ?? $_SESSION['app']) . ':' . $blacklist);
                        break;
                case 'hwid':
                        $query = mysql\query("DELETE FROM `bans` WHERE `app` = ? AND `hwid` = ?",[$secret ?? $_SESSION['app'], $blacklist]);
                        cache\purgePattern('KeyAuthBlacklist:' . ($secret ?? $_SESSION['app']) . ':*:' . $blacklist);
                        break;
                case 'region':
                        $query = mysql\query("DELETE FROM `bans` WHERE `app` = ? AND `region` = ?",[$secret ?? $_SESSION['app'], $blacklist]);
                        break;
                case 'country':
                        $query = mysql\query("DELETE FROM `bans` WHERE `app` = ? AND `country` = ?",[$secret ?? $_SESSION['app'], $blacklist]);
                        break;
                case 'asn':
                        $query = mysql\query("DELETE FROM `bans` WHERE `app` = ? AND `asn` = ?",[$secret ?? $_SESSION['app'], $blacklist]);
                        break;
                default:
                        return 'invalid';
        }
        if ($query->affected_rows > 0) {
                if ($_SESSION['role'] == "seller" || !is_null($secret)) {
                        cache\purge('KeyAuthBlacks:' . ($secret ?? $_SESSION['app']));
                }
                return 'success';
        } else {
                return 'failure';
        }
}

