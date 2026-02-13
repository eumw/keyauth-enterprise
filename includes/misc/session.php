<?php

namespace misc\session;

use misc\etc;
use misc\cache;
use misc\mysql;

function killAll($secret = null)
{
        $query = mysql\query("DELETE FROM `sessions` WHERE `app` = ?",[$secret ?? $_SESSION['app']]);
        if ($query->affected_rows > 0) {
                cache\purgePattern('KeyAuthState:' . ($secret ?? $_SESSION['app']));
                cache\purge('KeyAuthSessions:' . ($secret ?? $_SESSION['app']));
                return 'success';
        } else {
                return 'failure';
        }
}
function killSingular($id, $secret = null)
{
        $id = etc\sanitize($id);

        $query = mysql\query("SELECT `ip` FROM `sessions` WHERE `app` = ? AND `id` = ?",[$secret ?? $_SESSION['app'], $id]);
        $row = mysqli_fetch_array($query->result);

        $query = mysql\query("DELETE FROM `sessions` WHERE `app` = ? AND `id` = ?",[$secret ?? $_SESSION['app'], $id]);
        if ($query->affected_rows > 0) {
                global $cfZoneIdCache;
                global $cfApiKeyCache;

                $appSecret = ($secret ?? $_SESSION['app']);
                $sessionId = strtolower($id);

                $url = "https://api.cloudflare.com/client/v4/zones/{$cfZoneIdCache}/purge_cache";
        
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array("files" => ["https://keyauth.win/api/KeyAuthState-{$appSecret}-{$sessionId}"])));
                
                $headers = array(
                "Authorization: Bearer {$cfApiKeyCache}",
                "Content-Type: application/json",
                );
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

                curl_exec($curl);
                cache\purge('KeyAuthState:' . ($secret ?? $_SESSION['app']) . ':' . $id);
                return 'success';
        } else {
                return 'failure';
        }
}
