<?php
include '../../includes/misc/autoload.phtml';
include '../../includes/api/1.0/autoload.phtml';
include '../../includes/api/shared/autoload.phtml';

header("Access-Control-Allow-Origin: *"); // allow browser applications to request API

set_exception_handler(function ($exception) {
        error_log("\n--------------------------------------------------------------\n");
        error_log($exception);
        error_log("\nRequest data:");
        error_log(print_r($_GET, true));
        error_log("\n--------------------------------------------------------------");
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        global $databaseUsername;
        $errorMsg = str_replace($databaseUsername, "REDACTED", $exception->getMessage());
        die(json_encode(array("success" => false, "message" => "Error: " . $errorMsg)));
});

if(strlen($_GET['sellerkey']) != 32) {
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(array("success" => false, "message" => "Seller key should be 32 characters long. Copy it from https://keyauth.cc/app/?page=seller-settings")));
}

// sincey paid Networking for rate limit whitelist
$whitelistLimit = array("ec86c456645336e1db99654eac015600", "ddfb1eb55de62a4ce8f7d54715d7d5aa");
if (!in_array($_GET['sellerkey'], $whitelistLimit)) {
        if (misc\cache\rateLimit("KeyAuthSellerLimit:" . $_GET['sellerkey'], 1, 60, 120)) {
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array("success" => false, "message" => "Seller key used too frequently! Try again in 1 minute. Keep seller key private, use webhook function. Also, store data after fetching first time.")));
        }
}

$typeCount = substr_count($_SERVER['REQUEST_URI'], '?type=') + substr_count($_SERVER['REQUEST_URI'], '&type=');
// Check if 'type' appears more than once
if ($typeCount > 1) {
        die(json_encode(array("success" => false, "message" => "You have \"type\" defined more than 1 time. Remove 1 of the types and keep only 1")));
}

$key = misc\etc\sanitize($_GET['key']);
$user = misc\etc\sanitize($_GET['user']);
$format = misc\etc\sanitize($_GET['format']);

function success($message){
        global $format;
        if ($format == "text") {
                die($message);
        } else {
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "$message"
                )));
        }
}
function error($message){
        global $format;
        if ($format == "text") {
                die($message);
        } else {
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => false,
                        "message" => "$message"
                )));
        }
}

$type = misc\etc\sanitize($_GET['type']);
if (!$type) {
        error("Type not specified");
}

$sellerkey = misc\etc\sanitize($_GET['sellerkey']);
$row = misc\cache\fetch('KeyAuthAppSeller:' . $sellerkey, "SELECT `secret`, `owner`, `banned`, `name`, `ownerid`, `sellerLogs`, `sellerApiWhitelist` FROM `apps` WHERE `sellerkey` = ?", [$sellerkey], 0); // using a different redis key name than normal API because seller API doesn't take name and ownerid

if ($row == "not_found") {
        http_response_code(404);
        error("No application found");
}

$secret = $row['secret'];
$owner = $row['owner'];
$name = $row['name'];
$banned = $row['banned'];
$ownerid = $row['ownerid'];
$logsEnabled = $row['sellerLogs'];
$sellerApiWhitelist = $row['sellerApiWhitelist'];
$sellerApiWhitelistPermissions = $row['sellerApiWhitelistRestrictions'];

if ($banned) {
        http_response_code(403);
        error("This application has been banned from KeyAuth.cc for violating terms");
}

if (!is_null($sellerApiWhitelist) && $sellerApiWhitelist != api\shared\primary\getIp()) {
        http_response_code(423);
        error("Restricted. Only authorized IPs may use this seller key!");
}

if(!isset($_GET['oauth2WhitelistAPI'])) {
        $sellrow = misc\cache\fetch('KeyAuthSellerCheck:' . $owner, "SELECT `role`,`expires` FROM `accounts` WHERE `username` = ?", [$owner], 0); // check if user who owns app is still has seller plan
        $role = $sellrow["role"];
        
        if ($role !== "seller") {
                http_response_code(403);
                error("Not authorized to use SellerAPI, please upgrade");
        }
}

if (strlen($_SERVER['REQUEST_URI']) <= 900 && $logsEnabled) {
        misc\mysql\query("INSERT INTO `sellerLogs` (`ip`, `path`, `date`, `app`) VALUES (?, ?, ?, ?)", [api\shared\primary\getIp(), misc\etc\sanitize('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']), time(), $secret]);
        misc\mysql\query("DELETE FROM `sellerLogs` WHERE `app` = ? AND `id` NOT IN ( SELECT `id` FROM ( SELECT `id` FROM `sellerLogs` WHERE `app` = ? ORDER BY `id` DESC LIMIT 20) foo );", [$secret, $secret]);
}

switch ($type) {
        case 'add':
                $expiry = misc\etc\sanitize($_GET['expiry']);
                $level = misc\etc\sanitize($_GET['level']);
                $owner = misc\etc\sanitize($_GET['owner']);
                $letters = misc\etc\sanitize($_GET['character']);
                $note = misc\etc\sanitize($_GET['note']);

                $payload = file_get_contents('php://input'); // get JSON payload from e-commerce systems that could be contacting SellerAPI. the quanity those e-commerce systems supply supersedes the quanity supplied in query paramater
                /*
                        Known e-commerce systems that automatically prioritize their quanity supplied than the ?amount=1 paramater:
                                - Sell.app
                                - Sellix.io
                                - Shoppy.gg
                */
                $json = json_decode($payload);
                $data = $json->data;
                $amount = misc\etc\sanitize($data->quantity) ?? misc\etc\sanitize($data->order->quantity) ?? misc\etc\sanitize($data->Quantity) ?? misc\etc\sanitize($_GET['amount']);

                if (is_null($expiry)) {
                        http_response_code(406);
                        error("Expiry not set");
                }
                if (!is_numeric($expiry)){
                        http_response_code(400);
                        error("Expiry must a number of days");
                }

                if (!isset($amount)) {
                        $amount = "1";
                }
                if (!is_numeric($amount)){
                        $amount = "1";
                }

                if (!isset($level)) {
                        $level = "1";
                }
                if (!is_numeric($level)) {
                        $level = "1";
                }
                if(strlen($level) > 12) {
                        error("Level too long");
                }

                if (is_null($owner)) {
                        $owner = "SellerAPI";
                }

                if(is_null($letters)){
                        $letters = "1";
                }

                if (\misc\cache\rateLimit("SellerAPIKeyAdds:" . $secret, $amount, 86400, 5000)) {
                        http_response_code(429);
                        error("You can only generate 5000 keys per 24 hours with SellerAPI.");
                }

                $mask = misc\etc\sanitize($_GET['mask']);

                if($mask == "XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX"){
                        $mask = "******-******-******-******-******-******";
                }

                if (empty($mask)) {
                        $mask = "******-******-******-******-******-******";
                }

                $key = misc\license\createLicense($amount, $mask, $expiry, $level, $note, 86400, $secret, $owner, $letters);
                switch ($key) {
                        case 'max_keys':
                                error("You can only generate 100 licenses at a time");
                                break;
                        case 'dupe_custom_key':
                                error("Can't do custom key with amount greater than one");
                                break;
                        default:
                                if ($amount > 1) {
                                        if ($format == "text") {
                                                $keys = NULL;
                                                for ($i = 0; $i < count($key); $i++){
                                                        $keys .= "" . $key[$i] . "\n";
                                                }
                                                $keys = preg_replace(

                                                        '~[\r\n]+~',

                                                        "\r\n",

                                                        trim($keys)
                                                );
                                                success($keys);
                                        } else {
                                                http_response_code(302);
                                                header('Content-Type: application/json; charset=utf-8');
                                                die(json_encode(array(
                                                        "success" => true,
                                                        "message" => "Licenses successfully generated",
                                                        "keys" => $key
                                                )));
                                        }
                                } else {
                                        if ($format == "text") {
                                                success(array_values($key)[0]);
                                        } else {
                                                header('Content-Type: application/json; charset=utf-8');
                                                die(json_encode(array(
                                                        "success" => true,
                                                        "message" => "License Successfully Generated",
                                                        "key" => array_values($key)[0]
                                                )));
                                        }
                                }
                }
        case 'addtime':
                $resp = misc\license\addTime($_GET['time'], 86400, $secret);
                switch ($resp) {
                        case 'failure':
                                http_response_code(500);
                                error("Failed to add time! Key must not be used.");
                                break;
                        case 'success':
                                success("Added time to unused licenses!");
                                break;
                        default:
                                http_response_code(400);
                                error("Unhandled Error! Contact us if you need help");
                                break;
                }
        case 'setvar':
                if(empty( $_GET['var'])) {
                        error("Must specify variable name");
                }
                if(empty( $_GET['data'])) {
                        error("Must specify variable data");
                }
                $resp = misc\user\setVariable($user, $_GET['var'], $_GET['data'], $secret, $_GET['readOnly']); 
                match($resp){
                        'missing' => error("No users found!"),
                        'failure' => error("Failed to set variable!"),
                        'success' => success("Successfully set variable!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'getvar':
                $var = misc\etc\sanitize($_GET['var']);

                $row = misc\cache\fetch('KeyAuthUserVar:' . $secret . ':' . $var . ':' . $user, "SELECT `data`, `readOnly` FROM `uservars` WHERE `name` = ? AND `user` = ? AND `app` = ?", [$var, $user, $secret], 0);

                if ($row == "not_found") {
                        http_response_code(404);
                        error("Variable not found for user");
                }

                $data = $row['data'];

                if ($format == "text") {
                        success($data);
                } else {
                        header('Content-Type: application/json; charset=utf-8');
                        die(json_encode(array(
                                "success" => true,
                                "message" => "Successfully retrieved variable",
                                "response" => $data
                        )));
                }
        case 'assignkey':
                $query = misc\mysql\query("SELECT `banned`, `expires`, `status`, `level` FROM `keys` WHERE `key` = ? AND `app` = ?", [$key, $secret]);
                        
                if ($query->affected_rows === 0) {
                        error("License not found!");
                } elseif ($query->num_rows > 0){
                        while ($row = mysqli_fetch_array($query->result)){
                                $expires = $row['expires'];
                                $status = $row['status'];
                                $level = $row['level'];
                                $banned = $row['banned'];
                        }
        
                        if ($status == "Used"){
                                error("Key is already assigned to a user!");
                        }
        
                        if (!is_null($banned)){
                                error("Key is banned, and can not be assigned!");
                        }
        
                        $expiry = $expires + time();
        
                        $query = misc\mysql\query("SELECT `name` FROM `subscriptions` WHERE `app` = ? AND `level` = ?", [$secret, $level]);
                        $subName = mysqli_fetch_array($query->result)['name'];
        
                        $resp = misc\user\extend($user, $subName, $expiry, 0, $secret);
                        switch ($resp){
                        case 'missing':
                                error("Username not found!");
                                break;
                        case 'sub_missing':
                                error("Subscription not found!");
                                break;
                        case 'failure':
                                error("Failed to upgrade!(aka assign key)");
                                break;
                        case 'success':
                                misc\mysql\query("UPDATE `keys` SET `status` = 'Used', `usedon` = ?, `usedby` = ? WHERE `key` = ? AND `app` = ?", [time(), $user, $key, $secret]);
                                misc\cache\purge('KeyAuthKeys:' . $secret . ':' . $key);
                                misc\cache\purge('KeyAuthSubs:' . $secret . ':' . $user);
                                success("Successfully Assigned Key To User!");
                                break;
                        default:
                                error("Unhandled Error! Contact support if you need help!");
                                break;
                        }
                }
        case 'massUserVarDelete':
                $name = misc\etc\sanitize($_GET['name']);

                $query = misc\mysql\query("DELETE FROM `uservars` WHERE `name` = ? AND `app` = ?", [$name, $secret]);
                if ($query->affected_rows > 0) {
                        misc\cache\purgePattern('KeyAuthUserVar:' . $secret);
                        success("Successfully deleted user variables with that name!");
                } else {
                        error("Failed to delete user variables with that name!");
                }
        case 'addAccount':
                /*
                        Create Manager or Reseller accounts through SellerAPI
                        
                        This is highly recommended instead of sharing your account with someone.
                        
                        Sharing your account is against our ToS plus often leads to the person you're sharing with doing something malicious (if they turn on you)
                */
                $resp = misc\account\addAccount($user, $_GET['role'], $_GET['email'], $_GET['pass'], $_GET['keylevels'], $owner, $name, $_GET['perms'] ?? 2047);
                match($resp){
                        'invalid_perms' => error("Invalid permission value. You must have at least one permission flag enabled!"),
                        'invalid_role' => error("Role options are Manager or Reseller!"),
                        'username_taken' => error("Username already taken!"),
                        'email_taken' => error("Email already taken!"),
                        'failure' => error("Failed to add account!"),
                        'success' => success("Successfully added account!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'deleteAccount':
                $query = misc\mysql\query("DELETE FROM `accounts` WHERE `owner` = ? AND `app` = ? AND `username` = ?", [$owner, $name, $user]);
                if ($query->affected_rows > 0) {
                        success("Successfully deleted account!");
                } else {
                        error("Failed to delete account!");
                }
        case 'fetchallblacks':
                $rows = misc\cache\fetch('KeyAuthBlacks:' . $secret, "SELECT `hwid`, `ip`, `type` FROM `bans` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No blacklists found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved blacklists",
                        "blacklists" => $rows
                )));
        case 'fetchallsubs':
                $rows = misc\cache\fetch('KeyAuthSubscriptions:' . $secret, "SELECT `name`, `level` FROM `subscriptions` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No subscriptions found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved subscriptions",
                        "subs" => $rows
                )));
        case 'fetchalluservars':
                $rows = misc\cache\fetch('KeyAuthUserVars:' . $secret, "SELECT `name`, `data`, `user` FROM `uservars` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No user variables Found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved user variables",
                        "vars" => $rows
                )));
        case 'fetchallfiles':
                $rows = misc\cache\fetch('KeyAuthFiles:' . $secret, "SELECT `id`, `url` FROM `files` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No files Found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved files",
                        "files" => $rows
                )));
        case 'fetchallvars':
                $rows = misc\cache\fetch('KeyAuthVars:' . $secret, "SELECT `varid`, `msg`,`authed` FROM `vars` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No variables Found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved variables",
                        "vars" => $rows
                )));
        case 'addvar':
                if(strlen($_GET['data']) > 1000) error("Data is too long. Must be less than 1000 characters. Use files for larger data");

                if (!is_numeric($_GET['authed'])) error("Authed paramater must be 1 if you want to require login first, or 0 if you don't want to.");

                $resp = misc\variable\add($_GET['name'], $_GET['data'], $_GET['authed'], $secret);
                match($resp){
                        'exists' => error("Varialbe name already exists!"),
                        'too_long' => error("Variable too long! Must be 1000 characters or less!"),
                        'failure' => error("Failed to create variable!"),
                        'success' => success("Successfully created variable!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'addsub':
                if(strlen($_GET['level']) > 12) {
                        error("Level too long");
                }
                if(!is_numeric($_GET['level'])) {
                        error("Level must be a number");
                }

                $resp = misc\sub\add($_GET['name'], $_GET['level'], $secret);
                match($resp){
                        'failure' => error("Failed to create subscription!"),
                        'success' => success("Successfully created subscription!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delappsub':
                $resp = misc\sub\deleteSingular($_GET['name'], $secret);
                match($resp){
                        'failure' => error("Failed to delete subscription!"),
                        'success' => success("Successfully deleted subscription!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'pauseuser':
                $resp = misc\user\pauseUser($_GET['username'], $secret);
                match($resp){
                        'failure' => error("Failed to pause user!"),
                        'success' => success("Successfully paused user!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'unpauseuser':
                $resp = misc\user\unpauseUser($_GET['username'], $secret);
                match($resp){
                        'failure' => error("Failed to unpause user!"),
                        'success' => success("Successfully unpaused user!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'addchannel':
                if(is_null($_GET['name'])) {
                        error("Channel name not specified");
                }

                $resp = misc\chat\createChannel($_GET['name'], $_GET['delay'], $secret);
                match($resp){
                        'failure' => error("Failed to create channel!"),
                        'success' => success("Successfully created channel!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delchannel':
                $resp = misc\chat\deleteChannel($_GET['name'], $secret);
                match($resp){
                        'failure' => error("Failed to delete channel!"),
                        'success' => success("Successfully deleted channel!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'clearchannel':
                $resp = misc\chat\clearChannel($_GET['name'], $secret);
                match($resp){
                        'failure' => error("Failed to clear channel!"),
                        'success' => success("Successfully cleared channel!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'muteuser':
                if (!is_numeric($_GET['time'])) error("Invalid time paramater, must be number");

                $timeout = $_GET['time'] + time();
                $resp = misc\chat\muteUser($_GET['user'], $timeout, $secret);
                match($resp){
                        'missing' => error("User doesn't exist!"),
                        'failure' => error("Failed to mute user!"),
                        'success' => success("Successfully muted user!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'unmuteuser':
                $resp = misc\chat\unMuteUser($_GET['user'], $secret);
                match($resp){
                        'failure' => error("Failed to unmute user!"),
                        'success' => success("Successfully unmuted user!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'kill':
                $resp = misc\session\killSingular($_GET['sessid'], $secret);
                match($resp){
                        'failure' => error("Failed to kill session!"),
                        'success' => success("Successfully killed session!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'killall':
                $resp = misc\session\killAll($secret);
                match($resp){
                        'failure' => error("Failed to kill all sessions!"),
                        'success' => success("Successfully killed all sessions!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'addwebhook':
                if (!is_numeric($_GET['authed'])) error("Authed paramater must be 1 if you want to require login first, or 0 if you don't want to.");

                $resp = misc\webhook\add($_GET['webid'], $_GET['baseurl'], $_GET['ua'], $_GET['authed'], $secret);
                match($resp){
                        'invalid_url' => error("URL isn't a valid URL!"),
                        'no_local' => error("URL can't be a local path! Must be a remote URL accessible by the open internet!"),
                        'failure' => error("Failed to add webhook!"),
                        'success' => success("Successfully added webhook!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };

    case 'delallwebhooks':
        $resp = misc\webhook\deleteAll($secret);
        match($resp){
                'failure' => error("Failed to find any webhooks!"),
                'success' => success("Successfully deleted all webhooks!"),
                default => error("Unhandled Error! Contact support if you need help!")
        };
    case 'black':
                $ipaddr = misc\etc\sanitize($_GET['ip']);
                $hwid = misc\etc\sanitize($_GET['hwid']);

                if (!empty($hwid)) {
                        misc\blacklist\add($hwid, "Hardware ID", $secret);
                }

                if (!empty($ipaddr)) {
                        misc\blacklist\add($ipaddr, "IP Address", $secret);
                }
                
                success("Blacklist Addition Successful");
        case 'delblack':
                $resp = misc\blacklist\deleteSingular($_GET['data'], $_GET['blacktype'], $secret);
                match($resp){
                        'invalid' => error("Invalid blacklist type!"),
                        'failure' => error("Failed to delete blacklist!"),
                        'success' => success("Successfully deleted blacklist!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'appdetails':
                $row = misc\cache\fetch('KeyAuthApp:' . $name . ':' . $owner, "SELECT * FROM `apps` WHERE `ownerid` = ? AND `name` = ?",[$ownerid, $name], 0);
                $ver = $row["ver"];

                header('Content-Type: application/json; charset=utf-8');
                die (json_encode(array(
                        "success" => true,
                        "message" => "Fetched Application Details Successfully",
                        "appdetails" => array(
                                "name" => "$name",
                                "ownerid" => "$ownerid",
                                "secret" => "$secret",
                                "version" => "$ver" 
                        )
                )));
        case 'delblacks':
                $resp = misc\blacklist\deleteAll($secret);
                match($resp){
                        'failure' => error("Failed to delete all blacklists!"),
                        'success' => success("Successfully deleted all blacklists!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'addWhite':
                $resp = misc\whitelist\addWhite($_GET['ip'], $secret);
                match($resp){
                        'failure' => error("Failed to add whitelist!"),
                        'success' => success("Successfully added whitelist!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delWhite':
                $resp = misc\whitelist\deleteWhite($_GET['ip'], $secret);
                match($resp){
                        'failure' => error("Failed to delete whitelist!"),
                        'success' => success("Successfully deleted whitelist!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'activate':
                $pass = misc\etc\sanitize($_GET['pass']);
                $hwid = misc\etc\sanitize($_GET['hwid']);

                $resp = api\v1_0\register($user, $key, $pass, NULL, $hwid, $secret);
                switch ($resp) {
                        case 'username_taken':
                                error("Username Already Exists.");
                        case 'key_not_found':
                                error("Key Not Found.");
                        case 'key_already_used':
                                error("Key Already Used.");
                        case 'key_banned':
                                error("Your license is banned.");
                        case 'hwid_blacked':
                                error("HWID is blacklisted");
                        case 'no_subs_for_level':
                                error("No active subscriptions found.");
                        default:
                                header('Content-Type: application/json; charset=utf-8');
                                die(json_encode(array(
                                        "success" => true,
                                        "message" => "Logged in!",
                                        "info" => array(
                                                "username" => "$user",
                                                "subscriptions" => $resp,
                                                "ip" => $_SERVER["HTTP_X_FORWARDED_FOR"]
                                        )
                                )));
                }
        case 'resetpw':
                $passwd = misc\etc\sanitize($_GET['passwd']);
                if (!is_null($passwd)) $passwd = password_hash($passwd, PASSWORD_BCRYPT);
                $query = misc\mysql\query("UPDATE `users` SET `password` = NULLIF(?,'') WHERE `username` = ? AND `app` = ?", [$passwd, $user, $secret]);

                if ($query->affected_rows > 0) {
                        misc\cache\purge('KeyAuthUser:' . $secret . ':' . $user);
                        success("Password reset successful");
                } else {
                        http_response_code(500);
                        error("Failed To reset password");
                }
        case 'editemail':
                $email = misc\etc\sanitize($_GET['email']);
                $resp = misc\user\changeEmail($user, $email, $secret);
                match($resp){
                        'failure' => error("Failed to change email!"),
                        'success' => success("Successfully changed email!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'editusername':
                $currentUsername = misc\etc\sanitize($_GET['currentUsername']);
                $newUsername = misc\etc\sanitize($_GET['newUsername']);
                $sessionID = misc\etc\sanitize($_GET['sessionID']);
        
                $resp = misc\user\changeUsername($currentUsername, $newUsername, $secret);
                switch($resp){
                        case 'already_used':
                                error("Username already used!");
                                break;
                        case 'failure':
                                error("Failed to change username!");
                                break;
                        case 'success':
                                misc\session\killSingular($sessionID, $secret);
                                success("Successfully changed username, user logged out!");
                                break;
                        default:
                                error("Unhandled Error! Contact support if you need assistance!");
                                break;
                } 
        case 'editsub':
                $sub = misc\etc\sanitize($_GET['sub']);
                $level = misc\etc\sanitize($_GET['level']);
                $query = misc\mysql\query("UPDATE `subscriptions` SET `level` = ? WHERE `name` = ? AND `app` = ?", [$level, $sub, $secret]);

                if ($query->affected_rows > 0) {
                        success("Subscription successfully edited");
                } else {
                        error("Failed to edit subscription");
                }
        case 'setnote':
                $note = misc\etc\sanitize($_GET['note']);
                if(strlen($note) > 69) {
                        error("Note must be less than 69 characters");
                }

                $query = misc\mysql\query("UPDATE `keys` SET `note` = ? WHERE `key` = ? AND `app` = ?", [$note, $key, $secret]);

                if ($query->affected_rows > 0) {
                        misc\cache\purge('KeyAuthKey:' . ($secret ?? $_SESSION['app']) . ':' . $key);
                        misc\cache\purge('KeyAuthKeys:' . ($secret ?? $_SESSION['app']));
                        success("Successfully set note");
                } else {
                        error("Failed to set note");
                }
        case 'fetchnote':
               $row = misc\cache\fetch('KeyauthKey:' . $secret . ':' . $key, "SELECT `note` FROM `keys` WHERE `app` = ? AND `key` = ?",[$secret, $key], 0);

               if ($row == "not_found"){
                die(json_encode(array(
                        "success" => false,
                        'message' => "Key not found"
                )));
               }

               $note = $row["note"];
               die(json_encode(array(
                "note" => $note,
               )));
        case 'countsubs':
                $name = misc\etc\sanitize($_GET['name']);

                if (is_null($name)) {
                        error("Paramater with name \"name\" missing");
                }

                $row = misc\cache\fetch('KeyAuthSubStats:' . $secret . ':' . $name, "SELECT COUNT(*) AS 'numSubs' FROM `subs` WHERE `app` = ? AND `subscription` = ? AND `expiry` > ?", [$secret, $name, time()], 0, 1800, "ssi");
                $numSubs = $row['numSubs'];

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Subscription count found successfully",
                        "count" => $numSubs
                )));
        case 'editvar':
                if(strlen($_GET['data']) > 1000) error("Data is too long. Must be less than 1000 characters. Use files for larger data");
                
                $varid = misc\etc\sanitize($_GET['varid']);
                $data = misc\etc\sanitize($_GET['data']);
                misc\mysql\query("UPDATE `vars` SET `msg` = ? WHERE `varid` = ? AND `app` = ?", [$data, $varid, $secret]);
                misc\cache\purge('KeyAuthVar:' . $secret . ':' . $varid);
                success("Variable Edit Successful");
        case 'retrvvar':
                $name = misc\etc\sanitize($_GET['name']);
                $row = misc\cache\fetch('KeyAuthVar:' . $secret . ':' . $name, "SELECT `msg`, `authed` FROM `vars` WHERE `varid` = ? AND `app` = ?", [$name, $secret], 0); // API uses `authed` column so it's best to use it here too to not have two different redis keys

                if ($row == "not_found") {
                        error("Variable not found");
                }

                $authed = $row["authed"];
                $data = $row["msg"];

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => $data,
                        "authed" => $authed ? "true" : "false"
                )));
        case 'delvar':
                $resp = misc\variable\deleteSingular($_GET['name'], $secret);
                match($resp){
                        'failure' => error("Failed to delete variable!"),
                        'success' => success("Successfully deleted variable!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'pauseapp':
                $query = misc\mysql\query("SELECT `expiry` FROM `subs` WHERE `app` = ? AND `expiry` > ?", [$secret, time()]);
                while ($row = mysqli_fetch_array($query->result)) {
                        $expires = $row['expiry'];
                        $exp = $expires - time();
                        misc\mysql\query("UPDATE `subs` SET `paused` = 1, `expiry` = ? WHERE `app` = ? AND `id` = ?", [$exp, $secret, $row['id']]);
                }
                $query = misc\mysql\query("UPDATE `apps` SET `paused` = 1 WHERE `secret` = ?", [$secret]);

                misc\cache\purge('KeyAuthApp:' . $name . ':' . $ownerid);
                misc\cache\purgePattern('KeyAuthSubs:' . $secret);

                if ($query->affected_rows > 0) {
                        success("Successfully paused application");
                } else {
                        error("Failed to pause application");
                }
        case 'pausesub':
                $subscription = misc\etc\sanitize($_GET['subscription']);
                $resp = misc\sub\pause($subscription, $secret);
                match($resp){
                        'success' => success("Successfully paused subscription!"),
                        'failure' => error("Failed to pause subscription!"),
                        default => error("Unhandled Error! Contact support for further assistance!")
                };
        case 'unpausesub':
                $subscription = misc\etc\sanitize($_GET['subscription']);
                $resp = misc\sub\unpause($subscription, $secret);
                match($resp){
                        'success' => success("Successfully unpaused subscription!"),
                        'failure' => error("Failed to unpause subscription!"),
                        default => error("Unhandled Error! Contact support for further assistance!")
                };
        case 'unpauseapp':
                $query = misc\mysql\query("SELECT `expiry` FROM `subs` WHERE `app` = ? AND `paused` = 1", [$secret]);
                while ($row = mysqli_fetch_array($query->result)) {
                        $expires = $row['expiry'];
                        $exp = $expires + time();
                        misc\mysql\query("UPDATE `subs` SET `paused` = 0, `expiry` = ? WHERE `app` = ? AND `id` = ?", [$exp, $secret, $row['id']]);
                }
                $query = misc\mysql\query("UPDATE `apps` SET `paused` = 0 WHERE `secret` = ?", [$secret]);

                misc\cache\purge('KeyAuthApp:' . $name . ':' . $ownerid);
                misc\cache\purgePattern('KeyAuthSubs:' . $secret);

                if ($query->affected_rows > 0) {
                        success("Successfully unpaused application");
                } else {
                        error("Failed to unpause application");
                }
    case 'stats':
                $row = misc\cache\fetch('KeyAuthAppStatsSeller:' . $secret, "SELECT (SELECT COUNT(1) FROM `keys` WHERE `app` = ? AND `status` = 'Not Used') AS `unused`, (SELECT COUNT(1) FROM `keys` WHERE `app` = ? AND `status` = 'Used') AS `used`, (SELECT COUNT(1) FROM `keys` WHERE `app` = ? AND `status` = 'Paused') AS `paused`, (SELECT COUNT(1) FROM `keys` WHERE `app` = ? AND `status` = 'Banned') AS `banned`, (SELECT COUNT(1) FROM `webhooks` WHERE `app` = ?) AS `webhooks`, (SELECT COUNT(1) FROM `files` WHERE `app` = ?) AS `files`, (SELECT COUNT(1) FROM `vars` WHERE `app` = ?) AS `vars`, (SELECT COUNT(1) FROM `accounts` WHERE `owner` = ? AND `app` = ? AND `role` = 'Reseller') AS `resellers`, (SELECT COUNT(1) FROM `accounts` WHERE `owner` = ? AND `app` = ? AND `role` = 'Manager') AS `managers`;", [$secret, $secret, $secret, $secret, $secret, $secret, $secret, $owner, $secret, $owner, $secret], 0, 1800);
                $unused = $row["unused"];
                $used = $row["used"];
                $paused = $row["paused"];
                $banned = $row["banned"];
                $totalkeys = $unused + $used + $paused + $banned;
                $webhooks = $row["webhooks"];
                $files = $row["files"];
                $vars = $row["vars"];
                $resellers = $row["resellers"];
                $managers = $row["managers"];
                $totalaccs = $resellers + $managers;

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "unused" => "$unused",
                        "used" => "$used",
                        "paused" => "$paused",
                        "banned" => "$banned",
                        "totalkeys" => "$totalkeys",
                        "webhooks" => "$webhooks",
                        "files" => "$files",
                        "vars" => "$vars",
                        "resellers" => "$resellers",
                        "managers" => "$managers",
                        "totalaccs" => "$totalaccs"
                )));
        case 'addhwiduser':
                $hwid = misc\etc\sanitize($_GET['hwid']);
                $query = misc\mysql\query("SELECT `hwid` FROM `users` WHERE `username` = ? AND `app` = ?", [$user, $secret]);
                $row = mysqli_fetch_array($query->result);
                $newHwid = $row["hwid"];

                $newHwid = $newHwid .= $hwid;

                misc\mysql\query("UPDATE `users` SET `hwid` = ? WHERE `username` = ? AND `app` = ?", [$newHwid, $user, $secret]);

                misc\cache\purge('KeyAuthUser:' . $secret . ':' . $user);

                success("Added HWID");
        case 'addhash':
                $resp = misc\app\addHash($_GET['hash'], $secret);
                match($resp){
                        'failure' => error("Failed to add hash!"),
                        'success' => success("Successfully added hash!"),
                        default => error("Unhandled Error! Contact support if you need help!")
                };
        case 'getkey':
                $row = misc\cache\fetch('KeyAuthKeyFromUser:' . $secret . ':' . $user, "SELECT `key` FROM `keys` WHERE `usedby` = ? AND `app` = ?", [$user, $secret], 0);
                if ($row == "not_found") {
                        error("License not found");
                }
                $key = $row["key"];

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "key" => $key
                )));
        case 'userdata':
                $query = misc\mysql\query("SELECT * FROM users WHERE username = ? AND app = ?", [$user, $secret]);
                if ($query->num_rows < 1) {
                        error("User not found");
                }

                $row = mysqli_fetch_array($query->result);
        
                $hwid = $row['hwid'];
                $ip = $row['ip'];
                $createdate = $row['createdate'];
                $lastlogin = $row['lastlogin'];
                $cooldown = $row["cooldown"];
                $password = $row['password'];
                $token = md5(substr($row["password"], -5));
                $banned = $row["banned"];
        
                $query = misc\mysql\query("SELECT `subscription`, `key`, `expiry` FROM `subs` WHERE `user` = ? AND `app` = ? AND `expiry` > ?", [$user, $secret, time()]);
                while ($r = mysqli_fetch_assoc($query->result)) {
                        $subData[] = $r;
                }
                $subscriptions = ($query->num_rows < 1) ? [] : $subData;
                
                $query = misc\mysql\query("SELECT `name`, `data` FROM `uservars` WHERE `user` = ? AND `app` = ?", [$user, $secret]);
                while ($r = mysqli_fetch_assoc($query->result)) {
                        $varData[] = $r;
                }
                $userVars = ($query->num_rows < 1) ? [] : $varData;

                header('Content-Type: application/json; charset=utf-8');
                // success
                die(json_encode(array(
                    "success" => true,
                    "username" => $user,
                    "subscriptions" => $subscriptions,
                    "uservars" => $userVars,
                    "ip" => $ip,
                    "hwid" => $hwid,
                    "createdate" => $createdate,
                    "lastlogin" => $lastlogin,
                    "cooldown" => $cooldown,
                    "password" => $password,
                    "token" => $token,
                    "banned" => $banned
                )));
        case 'extend':
                if (!is_numeric($_GET['expiry'])) error("Expiry not set correctly, must be number of days");

                $expiry = $_GET['expiry'] * 86400 + time(); // 86400 is the number of seconds in a day since we're using unix time
                $resp = misc\user\extend($user, $_GET['sub'], $expiry, $_GET['activeOnly'], $secret);
                match($resp){
                        'missing' => error("User(s) not found!"),
                        'sub_missing' => error("Subscription not found!"),
                        'date_past' => error("Subscription expiry must be set in the future!"),
                        'failure' => error("Failed to extend user(s)"),
                        'success' => success("Successfully extended user(s)!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'subtract':
                $resp = misc\user\subtract($user, $_GET['sub'], $_GET['seconds'], $secret);
                match($resp){
                        'invalid_seconds' => error("Seconds specified must be greater than zero!"),
                        'failure' => error("Failed to subtract from susbcriptions!"),
                        'success' => success("Successfully subtracted time from subscription!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'verify':
                $query = misc\mysql\query("SELECT 1 FROM `keys` WHERE `app` = ? AND `key` = ?", [$secret, $key]);

                if ($query->num_rows > 0) {
                        success("Key Successfully Verified");
                } else {
                        http_response_code(406);
                        error("Key Not Found");
                }
        case 'verifyuser':
                $query = misc\mysql\query("SELECT 1 FROM `users` WHERE `app` = ? AND `username` = ?", [$secret, $user]);

                if ($query->num_rows > 0) {
                        success("User Successfully Verified");
                } else {
                        http_response_code(406);
                        error("User Not Found");
                }
        case 'del':
                $resp = misc\license\deleteSingular($key, $_GET['userToo'], $secret);
                match($resp){
                        'failure' => error("Failed to delete license!"),
                        'success' => success("Successfully deleted license!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
    case 'delmultiple':
        $resp = misc\license\deleteMultiple($_GET['key'], $_GET['userToo'], $secret);
        match ($resp){
                'failure' => error("Failed to delete license(s)"),
                'success' => success("Successfully deleted license(s)"),
                default => error("Unhandled Error! Contact support if you need help!")
        };
        case 'ban':
                $resp = misc\license\ban($key, $_GET['reason'], $_GET['userToo'], $secret);
                match($resp){
                        'failure' => error("Failed to ban license!"),
                        'success' => success("Successfully banned license!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'unban':
                $resp = misc\license\unban($key, $secret);
                match($resp){
                        'failure' => error("Failed to unban license!"),
                        'success' => success("Successfully unbanned license!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'banuser':
		if(empty($_GET['reason'])) {
			error("Ban reason is missing");
		}

                $resp = misc\user\ban($user, $_GET['reason'], $secret);
                match($resp){
                        'missing' => error("User not found!"),
                        'failure' => error("Failed to ban user!"),
                        'success' => success("Successfully banned user!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'unbanuser':
                $resp = misc\user\unban($user, $secret);
                match($resp){
                        'missing' => error("User not found!"),
                        'failure' => error("Failed to unban user!"),
                        'success' => success("Successfully unbanned user!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'deluservar':
                $resp = misc\user\deleteVar($user, $_GET['var'], $secret);
                match($resp){
                        'failure' => error("Failed to delete variable"),
                        'success' => success("Successfully deleted variable"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delsub':
                $resp = misc\user\deleteSub($user, $_GET['sub'], $secret);
                match($resp){
                        'failure' => error("Failed to delete subscription!"),
                        'success' => success("Successfully deleted subscription!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delunused':
                $resp = misc\license\deleteAllUnused($secret);
                match($resp){
                        'failure' => error("Didn't find any unused keys!"),
                        'success' => success("Successfully deleted all unused keys!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delused':
                $resp = misc\license\deleteAllUsed($secret);
                match($resp){
                        'failure' => error("Didn't find any used keys!"),
                        'success' => success("Successfully deleted all used keys!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'adduser':
                if (!is_numeric($_GET['expiry'])) error("Expiry not set correctly, must be number of days");

                if(empty($_GET['user'])) error("You must specify username.");

                $expiry = $_GET['expiry'] * 86400 + time(); // 86400 is the number of seconds in a day since we're using unix time
                $resp = misc\user\add($user, $_GET['sub'], $expiry, $secret, $_GET['pass']);
                match($resp){
                        'already_exist' => error("Username already exists!"),
                        'username_not_allowed' => error("Username not allowed!"),
                        'sub_paused' => error("Unable to create user while subscription is paused!"),
                        'sub_missing' => error("Subscription not found!"),
                        'date_past' => error("Subscription expiry must be set in the future!"),
                        'failure' => error("Failed to create user!"),
                        'success' => success("Successfully created user!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delexpusers':
                $resp = misc\user\deleteExpiredUsers($secret);
                match($resp){
                        'missing' => error("You have no users!"),
                        'failure' => error("No users are expired!"),
                        'success' => success("Successfully deleted expired users!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delallusers':
                $resp = misc\user\deleteAll($secret);
                match($resp){
                        'failure' => error("Failed to delete all users!"),
                        'success' => success("Successfully deleted all users!"),
                        default => error("Unhandled Error! Contact us if you need help")
                };
        case 'deluser':
                $resp = misc\user\deleteSingular($user, $secret);
                match($resp){
                        'failure' => error("Failed to delete user!"),
                        'success' => success("Successfully deleted user!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delalllicenses':
                $resp = misc\license\deleteAll($secret);
                match($resp){
                        'failure' => error("Didn't find any keys!"),
                        'success' => success("Deleted all keys!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delallvars':
                $resp = misc\variable\deleteAll($secret);
                match($resp){
                        'failure' => error("Failed to delete all variables!"),
                        'success' => success("Successfully deleted all variables!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'reset':
                die("Endpoint Deprecated, you can no longer use keys directly. A user is created from the key, and that user has a HWID and IP associated with it.");
        case 'resethash':
                $resp = misc\app\resetHash($secret, $name, $ownerid);
                match($resp){
                        'failure' => error("Failed to reset hash!"),
                        'success' => success("Successfully reset hash!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'setcooldown':
                $cooldown = misc\etc\sanitize($_GET['cooldown']);

                $query = misc\mysql\query("UPDATE `users` SET `cooldown` = ? WHERE `app` = ? AND `username` = ?", [$cooldown, $secret, $user]);

                if ($query->affected_rows > 0) {
                        misc\cache\purge('KeyAuthUserData:' . $secret . ':' . $user);
                        success("Cooldown set successfully!");
                }

                error("Failed to set cooldown!");
        case 'resetuser':
                $resp = misc\user\resetSingular($user, $secret);
                match($resp){
                        'failure' => error("Failed to reset user!"),
                        'success' => success("Successfully reset user!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'resetalluser':
                $resp = misc\user\resetAll($secret);
                match($resp){
                        'failure' => error("Failed to reset all users!"),
                        'success' => success("Successfully reset all users!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'upload':
                $url = misc\etc\sanitize($_GET['url']);

                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                        error("URL is invalid");
                }

                if (str_contains($url, "discord")) {
                        error("Discord's developers made files expire after 24 hours, use catbox.moe or pages.dev instead!");
                }

                if(str_contains($url, "localhost") || str_contains($url, "127.0.0.1") || str_contains($url, "file:/")) {
                        error("URL can't be a local path! Must be a remote URL accessible by the open internet");
                }

                $file = file_get_contents($url);

                $filesize = strlen($file);

                if ($filesize > 75000000) {
                        error("File size limit is 75 MB.");
                }

                $id = misc\etc\generateRandomNum();
                $fn = basename($url);
                $fs = misc\etc\formatBytes($filesize);

                misc\mysql\query("INSERT INTO `files` (name, id, url, size, uploaddate, app) VALUES (?, ?, ?, ?, ?, ?)", [$fn, $id, $url, $fs, time(), $secret]);

                if ($format == "text") {
                        die("File ID " . $id . " Uploaded Successfully");
                } else {
                        header('Content-Type: application/json; charset=utf-8');
                        die(json_encode(array(
                                "success" => true,
                                "message" => "File ID " . $id . " Uploaded Successfully",
                                "id" => $id
                        )));
                }
        case 'fetchfile':
                $fileid = misc\etc\sanitize($_GET['id']);
                            
                $result = misc\mysql\query("SELECT `id`, `url`, `name`, `size`, `authed` FROM `files` WHERE `id` = ? AND `app` = ?", [$fileid, $secret]);
                $response = mysqli_fetch_assoc($result->result);
                            
                if (!$response) {
                        http_response_code(406);
                        error("No file Found with ID: $fileid");
                }
                            
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved file",
                        "file" => $response
                )));
        case 'editfile':
                $fileUrl = misc\etc\sanitize($_GET['url']);
                $fileAuthed = misc\etc\sanitize($_GET['authed']);
                $fileid = misc\etc\sanitize($_GET['id']);
                            
                // Initialize cURL session
                $ch = curl_init($fileUrl);
                            
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Fetch the entire content
                curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the response
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla KeyAuth');
                            
                $response = curl_exec($ch);
                            
                $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            
                if ($httpStatusCode != 200) {
                        error("Failed to fetch file. HTTP Status: $httpStatusCode");
                }
                            
                $contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
                            
                if ($contentLength <= 0) {
                        $body = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
                        $fileSizeActual = misc\etc\formatBytes(strlen($body));
                } else {
                        $fileSizeActual = misc\etc\formatBytes($contentLength);
                }
                            
                $fileName = basename(parse_url($fileUrl, PHP_URL_PATH));
                            
                curl_close($ch);
                            
                $result = misc\mysql\query("UPDATE `files` SET `name` = ?, `size` = ?, `url` = ?, `authed` = ? WHERE `id` = ? AND `app` = ?", [$fileName, $fileSizeActual, $fileUrl, $fileAuthed, $fileid, $secret]);
                            
                if ($result->affected_rows > 0) {
                        success("Successfully updated file");
                } else {
                        error("Failed to update file");
                }    
        case 'delfile':
                $resp = misc\upload\deleteSingular($_GET['fileid'], $secret);
                match($resp){
                        'failure' => error("Failed to delete all files!"),
                        'success' => success("Successfully deleted files!"),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delallfiles':
                $resp = misc\upload\deleteAll($secret);
                match($resp){
                        'failure' => error("Failed to delete all files!"),
                        'success' => success("Successfully deleted all files!"),
                        default => error("Unhandled Error! Contact us if you need help")
                };
        case 'fetchallchats':
                $rows = misc\cache\fetch('KeyAuthChats:' . $secret, "SELECT `name`, `delay` FROM `chats` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No chats found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved chats",
                        "chats" => $rows
                )));
        case 'fetchallwebhooks':
                $rows = misc\cache\fetch('KeyAuthWebhook:' . $secret, "SELECT `webid`, LEFT(`baselink`, 21) AS `short_baselink`, `useragent`, `app`, `authed` FROM `webhooks` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found"){
                        http_response_code(406);
                        error("No webhooks found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved all webhooks",
                        "webhooks" => $rows
                )));
        case 'fetchallsessions':
                $rows = misc\cache\fetch('KeyAuthSessions:' . $secret, "SELECT `id`, `credential`, `expiry`, `validated`, `ip` FROM `sessions` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No sessions found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved sessions",
                        "sessions" => $rows
                )));
        case 'fetchallbuttons':
                $rows = misc\cache\fetch('KeyAuthButtons:' . $secret, "SELECT `text`, `value` FROM `buttons` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No buttons found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved buttons",
                        "buttons" => $rows
                )));
        case 'fetchallmutes':
                $rows = misc\cache\fetch('KeyAuthMutes:' . $secret, "SELECT `user`, `time` FROM `chatmutes` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No mutes found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved mutes",
                        "mutes" => $rows
                )));
        case 'editchan':
                $name = misc\etc\sanitize($_GET['name']);
                $delay = misc\etc\sanitize($_GET['delay']);
                $delay = $delay * 60; // making it such that the delay is in the unit of minutes
                $query = misc\mysql\query("UPDATE `chats` SET `delay` = ? WHERE `app` = ? AND `name` = ?", [$delay, $secret, $name]);
                if ($query->affected_rows > 0) {
                        success("Successfully updated channel!");
                } else {
                        error("Failed To update channel!");
                }
        case 'fetchallusernames':
                $rows = misc\cache\fetch('KeyAuthUsernames:' . $secret, "SELECT `username` FROM `users` WHERE `app` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No users found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully Retrieved Usernames",
                        "usernames" => $rows
                )));
        case 'fetchallusers':
                $rows = misc\cache\fetch('KeyAuthUsers:' . $secret, "SELECT * FROM `users` WHERE `app` = ? LIMIT 520", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No users found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully Retrieved Users",
                        "users" => $rows
                )));
        case 'setseller':
                success("Seller Key Successfully Found");
        case 'setbalance':
                $username = misc\etc\sanitize($_GET['username']);
                if (empty($username)) {
                        error("Username Not Set");
                }

                $query = misc\mysql\query("SELECT 1 FROM `accounts` WHERE `app` = ? AND `username` = ?", [$name, $username]);
                if ($query->num_rows < 1) {
                        error("You don't own account you were attemping to modify balance for");
                }

                $dayamount = misc\etc\sanitize($_GET['day']);
                $weekamount = misc\etc\sanitize($_GET['week']);
                $monthamount = misc\etc\sanitize($_GET['month']);
                $threemonthamount = misc\etc\sanitize($_GET['threemonth']);
                $sixmonthamount = misc\etc\sanitize($_GET['sixmonth']);
                $lifetimeamount = misc\etc\sanitize($_GET['lifetime']);
                $yearamount = misc\etc\sanitize($_GET['year']);

                if (!isset($dayamount)) {
                        $dayamount = "0";
                }
                if (!isset($weekamount)) {
                        $weekamount = "0";
                }
                if (!isset($monthamount)) {
                        $monthamount = "0";
                }
                if (!isset($threemonthamount)) {
                        $threemonthamount = "0";
                }
                if (!isset($sixmonthamount)) {
                        $sixmonthamount = "0";
                }
                if (!isset($lifetimeamount)) {
                        $lifetimeamount = "0";
                }
                if (!isset($year)){
                        $yearamount = "0";
                }

                $query = misc\mysql\query("SELECT `balance` FROM `accounts` WHERE `username` = ?", [$username]);

                $row = mysqli_fetch_array($query->result);

                $balance = $row["balance"];

                $balance = explode("|", $balance);

                $day = $balance[0];
                $week = $balance[1];
                $month = $balance[2];
                $threemonth = $balance[3];
                $sixmonth = $balance[4];
                $lifetime = $balance[5];
                $year = $balance[6];

                $day = $day + $dayamount;

                $week = $week + $weekamount;

                $month = $month + $monthamount;

                $threemonth = $threemonth + $threemonthamount;

                $sixmonth = $sixmonth + $sixmonthamount;

                $lifetime = $lifetime + $lifetimeamount;

                $year = $year + $yearamount;

                $balance = $day . '|' . $week . '|' . $month . '|' . $threemonth . '|' . $sixmonth . '|' . $lifetime . '|' . $year;

                misc\mysql\query("UPDATE `accounts` SET `balance` = ? WHERE `username` = ?", [$balance, $username]);

                success("Balance Successfully Added");
        case 'getbalance':
                $resellerusername = misc\etc\sanitize($_GET['username']);
                $appname = misc\etc\sanitize($_GET["appname"]);
        
                $reseller = misc\mysql\query("SELECT * FROM `accounts` WHERE `username` = ? AND `app` = ? AND `owner` = ?", [$resellerusername, $appname, $owner]);
        
                if ($reseller->num_rows < 1) {
                        error("Reseller Account Doesn't Exist Or It Doesn't Belong To You");
                }
        
                $balance = mysqli_fetch_array($reseller->result);
        
                [$day, $week, $month, $threemonth, $sixmonth, $lifetime, $year] = explode("|", $balance["balance"]);
        
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array("success" => true,"balance" => array(
                        "day" => $day,
                        "week" => $week,
                        "month" => $month,
                        "three_month" => $three_month, 
                        "six_month" => $six_month,
                        "lifetime" => $lifetime,
                        "year" => $year,
                        "total_keys" => $day+$week+$month+$three_month+$six_month+$lifetime+$year))));
        case 'usersub':
                $rows = misc\cache\fetch('KeyAuthUserSubs:' . $secret, "SELECT * FROM `subs` WHERE `app` = ? AND `user` = ?", [$secret, $user], 1);

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully Retrieved User Subscription",
                        "subs" => $rows
                )));
        case 'getsettings':
                $row = misc\cache\fetch('KeyAuthApp:' . $name . ':' . $ownerid, "SELECT * FROM `apps` WHERE `ownerid` = ? AND `name` = ?", [$ownerid, $name], 0);
                if ($row["enabled"] == 0) {
                        $enabled = false;
                } else {
                        $enabled = true;
                }

                if ($row["hwidcheck"] == 0) {
                        $hwidcheck = false;
                } else {
                        $hwidcheck = true;
                }

                $ver = $row["ver"];
                $download = $row["download"];
                $webdownload = $row["webdownload"];
                $webhook = $row["webhook"];
                $resellerstore = $row["resellerstore"];
                $appdisabled = $row["appdisabled"];
                $usernametaken = $row["usernametaken"];
                $keynotfound = $row["keynotfound"];
                $keyused = $row["keyused"];
                $nosublevel = $row["nosublevel"];
                $usernamenotfound = $row["usernamenotfound"];
                $passmismatch = $row["passmismatch"];
                $hwidmismatch = $row["hwidmismatch"];
                $noactivesubs = $row["noactivesubs"];
                $hwidblacked = $row["hwidblacked"];
                $sellixsecret = $row["sellixsecret"];
                $cooldown = $row["cooldown"];

                if ($format == "text") {
                        echo $enabled ? 'true' : 'false';
                        echo "\n";
                        echo $hwidcheck ? 'true' : 'false' . "\n";
                        echo $ver . "\n";
                        echo $download . "\n";
                        echo $webdownload . "\n";
                        echo $webhook . "\n";
                        echo $resellerstore . "\n";
                        echo $appdisabled . "\n";
                        echo $usernametaken . "\n";
                        echo $keynotfound . "\n";
                        echo $keyused . "\n";
                        echo $nosublevel . "\n";
                        echo $usernamenotfound . "\n";
                        echo $passmismatch . "\n";
                        echo $hwidmismatch . "\n";
                        echo $noactivesubs . "\n";
                        echo $hwidblacked . "\n";
                        echo $sellixsecret . "\n";
                        echo $cooldown;
                        break;
                } else {
                        header('Content-Type: application/json; charset=utf-8');
                        die(json_encode(array(
                                "success" => true,
                                "message" => "Retrieved Settings Successfully",
                                "enabled" => $enabled,
                                "hwid-lock" => $hwidcheck,
                                "version" => "$ver",
                                "download" => "$download",
                                "webdownload" => "$webdownload",
                                "webhook" => "$webhook",
                                "resellerstore" => "$resellerstore",
                                "disabledmsg" => "$appdisabled",
                                "usernametakenmsg" => "$usernametaken",
                                "licenseinvalidmsg" => "$keynotfound",
                                "keytakenmsg" => "$keyused",
                                "nosubmsg" => "$nosublevel",
                                "userinvalidmsg" => "$usernamenotfound",
                                "passinvalidmsg" => "$passmismatch",
                                "hwidmismatchmsg" => "$hwidmismatch",
                                "noactivesubmsg" => "$noactivesubs",
                                "blackedmsg" => "$hwidblacked",
                                "sellixsecret" => "$sellixsecret",
                                "cooldown" => "$cooldown"
                        )));
                }
        case 'fetchallkeys':
                $rows = misc\cache\fetch('KeyAuthKeys:' . $secret, "SELECT * FROM `keys` WHERE `app` = ?", [$secret], 1, 1800);
        
                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No keys found");
                }
        
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully Retrieved Licenses",
                        "keys" => $rows
                        )));            
        case 'info':
                $row = misc\cache\fetch('KeyAuthKey:' . $secret . ':' . $key, "SELECT * FROM `keys` WHERE `app` = ? AND `key` = ?", [$secret, $key], 0);

                if ($row == "not_found") {
                        error("Key Not Found");
                }

                $expiry = $row["expires"];
                $status = $row["status"];
                $note = $row["note"];
                $level = $row["level"];
                $genby = $row["genby"];
                $usedby = $row["usedby"];
                $usedon = $row["usedon"];
                $gendate = $row["gendate"];
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "duration" => "$expiry",
                        "note" => "$note",
                        "status" => "$status",
                        "level" => "$level",
                        "createdby" => "$genby",
                        "usedby" => "$usedby",
                        "usedon" => "$usedon",
                        "creationdate" => "$gendate"
                )));
        case 'updatesettings':
                $enabled = misc\etc\sanitize($_GET['enabled']);
                $hwidcheck = misc\etc\sanitize($_GET['hwidcheck']);
                $ver = misc\etc\sanitize($_GET['ver']);
                $download = misc\etc\sanitize($_GET['download']);
                $webhook = misc\etc\sanitize($_GET['webhook']);
                $resellerstore = misc\etc\sanitize($_GET['resellerstore']);
                $appdisabled = misc\etc\sanitize($_GET['appdisabled']);
                $usernametaken = misc\etc\sanitize($_GET['usernametaken']);
                $keynotfound = misc\etc\sanitize($_GET['keynotfound']);
                $keyused = misc\etc\sanitize($_GET['keyused']);
                $nosublevel = misc\etc\sanitize($_GET['nosublevel']);
                $usernamenotfound = misc\etc\sanitize($_GET['usernamenotfound']);
                $passmismatch = misc\etc\sanitize($_GET['passmismatch']);
                $hwidmismatch = misc\etc\sanitize($_GET['hwidmismatch']);
                $noactivesubs = misc\etc\sanitize($_GET['noactivesubs']);
                $hwidblacked = misc\etc\sanitize($_GET['hwidblacked']);
                $sellixsecret = misc\etc\sanitize($_GET['sellixsecret']);

                if (!empty($enabled)) {
                        if ($enabled == "true") {
                                $enabled = 1;
                        } else if ($enabled == "false") {
                                $enabled = 0;
                        }
                        misc\mysql\query("UPDATE `apps` SET `enabled` = ? WHERE `sellerkey` = ?", [$enabled, $sellerkey]);
                }
                if (!empty($hwidcheck)) {
                        if ($hwidcheck == "true") {
                                $hwidcheck = 1;
                        } else if ($hwidcheck == "false") {
                                $hwidcheck = 0;
                        } else {
                                error("The hwidcheck value must be \"true\" or \"false\"");
                        }

                        misc\mysql\query("UPDATE `apps` SET `hwidcheck` = ? WHERE `sellerkey` = ?", [$hwidcheck, $sellerkey]);
                }
                if (!empty($ver)) {
                        misc\mysql\query("UPDATE `apps` SET `ver` = ? WHERE `sellerkey` = ?", [$ver, $sellerkey]);
                }
                if (!empty($download)) {
                        misc\mysql\query("UPDATE `apps` SET `download` = ? WHERE `sellerkey` = ?", [$download, $sellerkey]);
                }
                if (!empty($webhook)) {
                        misc\mysql\query("UPDATE `apps` SET `webhook` = ? WHERE `sellerkey` = ?", [$webhook, $sellerkey]);
                }
                if (!empty($resellerstore)) {
                        misc\mysql\query("UPDATE `apps` SET `resellerstore` = ? WHERE `sellerkey` = ?", [$resellerstore, $sellerkey]);
                }
                if (!empty($appdisabled)) {
                        misc\mysql\query("UPDATE `apps` SET `appdisabled` = ? WHERE `sellerkey` = ?", [$appdisabled, $sellerkey]);
                }
                if (!empty($usernametaken)) {
                        misc\mysql\query("UPDATE `apps` SET `usernametaken` = ? WHERE `sellerkey` = ?", [$usernametaken, $sellerkey]);
                }
                if (!empty($keynotfound)) {
                        misc\mysql\query("UPDATE `apps` SET `keynotfound` = ? WHERE `sellerkey` = ?", [$keynotfound, $sellerkey]);
                }
                if (!empty($keyused)) {
                        misc\mysql\query("UPDATE `apps` SET `keyused` = ? WHERE `sellerkey` = ?", [$keyused, $sellerkey]);
                }
                if (!empty($nosublevel)) {
                        misc\mysql\query("UPDATE `apps` SET `nosublevel` = ? WHERE `sellerkey` = ?", [$nosublevel, $sellerkey]);
                }
                if (!empty($usernamenotfound)){
                        misc\mysql\query("UPDATE `apps` SET `usernamenotfound` = ? WHERE `sellerkey` = ?", [$usernamenotfound, $sellerkey]);
                }
                if (!empty($passmismatch)) {
                        misc\mysql\query("UPDATE `apps` SET `passmismatch` = ? WHERE `sellerkey` = ?", [$passmismatch, $sellerkey]);
                }
                if (!empty($hwidmismatch)) {
                        misc\mysql\query("UPDATE `apps` SET `hwidmismatch` = ? WHERE `sellerkey` = ?", [$hwidmismatch, $sellerkey]);
                }
                if (!empty($noactivesubs)) {
                        misc\mysql\query("UPDATE `apps` SET `noactivesubs` = ? WHERE `sellerkey` = ?", [$noactivesubs, $sellerkey]);
                }
                if (!empty($hwidblacked)) {
                        misc\mysql\query("UPDATE `apps` SET `hwidblacked` = ? WHERE `sellerkey` = ?", [$hwidblacked, $sellerkey]);
                }
                if (!empty($sellixsecret)) {
                        misc\mysql\query("UPDATE `apps` SET `sellixsecret` = ? WHERE `sellerkey` = ?", [$sellixsecret, $sellerkey]);
                }

                misc\cache\purge('KeyAuthApp:' . $name . ':' . $ownerid);

                success("Settings Update Successful");
        case 'edit':
                $expiry = misc\etc\sanitize($_GET['expiry']);

                if(is_null($expiry)) {
                        error("You must set expiry value");
                }

                misc\mysql\query("UPDATE `keys` SET `expires` = ? WHERE `app` = ? AND `key` = ?", [$expiry, $secret, $key]);

                success("License Edit Successful");
        case 'addbutton':
                $resp = misc\button\addButton($_GET['text'], $_GET['value'], $secret);
                match($resp){
                        'success' => success("Succesfully added button!"),
                        'failure' => error("Failed to add button! You can't have two button with the same value."),
                        default => error("Unhandled Error! Contact support if you need help")
                };
        case 'delbutton':
                $resp = misc\button\deleteButton($_GET['value'], $secret);
                match($resp){
                        'success' => success("Successfully deleted button!"),
                        'failure' => error("Failed to delete button!"),
                        default => error("Unhandled Error! Contact support if you need help!")
                };
        case 'fetchalllogs':
                $rows = misc\cache\fetch('KeyAuthLogs:' . $secret, "SELECT * FROM `logs` WHERE `logapp` = ?", [$secret], 1, 1800);

                if ($rows == "not_found") {
                        http_response_code(406);
                        error("No logs found");
                }

                header('Content-Type: application/json; charset=utf-8');
                die(json_encode(array(
                        "success" => true,
                        "message" => "Successfully retrieved logs",
                        "logs" => $rows
                )));
        case 'dellogs':
                $resp = misc\logging\deleteAll($secret);
                match($resp){
                        'success' => success("Successfully deleted all logs!"),
                        'failure' => error("Failed to delete all logs!"),
                        default => error("Unhandled Error! Contact support if you need help!")
                };
        default:
                error("Type doesn't exist");
        }