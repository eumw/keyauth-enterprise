<?php
if (!isset($_SERVER["HTTP_X_SELLIX_SIGNATURE"]) && !isset($_SERVER["HTTP_X_SHOPPY_SIGNATURE"]) && !isset($_SERVER["HTTP_SIGNATURE"]))
{
        require '../../includes/dashboard/autoload.phtml';
        die("Request isn't coming from SellApp, Sellix, or Shoppy.");
}

include '../../includes/misc/autoload.phtml';
require '../../includes/dashboard/autoload.phtml';

if (isset($_SERVER["HTTP_X_SELLIX_SIGNATURE"]))
{
    $app = misc\etc\sanitize($_GET['app']);
    $query = misc\mysql\query("SELECT * FROM `apps` WHERE `secret` = ?", [$app]);
        
    if ($query->num_rows < 1)
    { // if no application was found with the supplied secret as the 'app' paramater
        die("Failure: application not found");
    }
    $row = mysqli_fetch_array($query->result);
    $name = $row["name"];
    $secret = $row["sellixsecret"];
        $dayproduct = $row["sellixdayproduct"];
        $weekproduct = $row["sellixweekproduct"];
        $monthproduct = $row["sellixmonthproduct"];
        $lifetimeproduct = $row["sellixlifetimeproduct"];

    $payload = file_get_contents('php://input');
    $header_signature = misc\etc\sanitize($_SERVER["HTTP_X_SELLIX_SIGNATURE"]);
    $signature = hash_hmac('sha512', $payload, $secret);
    if (!hash_equals($signature, $header_signature))
    { // if the sellix webhook secret the request was sent from didn't match the one set in the database
        die("Failure: authentication with sellix secret failed");
    }

    $json = json_decode($payload);
    $data = $json->data;
    $custom = $data->custom_fields; // getting custom fields, the hidden fields on KeyAuth sellix embed which provide sellix the KeyAuth username
    $un = misc\etc\sanitize($custom->username);
        
    $query = misc\mysql\query("SELECT `balance` FROM `accounts` WHERE `username` = ? AND `app` = ?", [$un, $name]);

    if ($query->num_rows < 1)
    { // if reseller not found
        die("Failure: No account with the supplied username under this application found");
    }
    // getting the balance of each key length for the specified reseller account
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

    $amount = misc\etc\sanitize($data->quantity); // find quantity of keys purchased
    // then given the duration of keys they purchased, add to their balance
        
        if(isset($_GET['expiry'])) {
                $expiry = misc\etc\sanitize($_GET['expiry']);
                $level = misc\etc\sanitize($_GET['level']);
                
                $key = misc\license\createLicense($amount, "******-******-******-******-******-******", $expiry, $level, NULL, 86400, $app, $un);
                
                $keys = NULL;
                for ($i = 0; $i < count($key); $i++) {
                        $keys .= "" . $key[$i] . "\n";
                }
                $keys = preg_replace(

                        '~[\r\n]+~',

                        "\r\n",

                        trim($keys)
                );
                die($keys);
        }
        
    switch (misc\etc\sanitize($data->product_id))
    {
                case $dayproduct:
                        $day = $day + $amount;
                        break;
                case $weekproduct:
                        $week = $week + $amount;
                        break;
                case $monthproduct:
                        $month = $month + $amount;
                        break;
                case $lifetimeproduct:
                        $lifetime = $lifetime + $amount;
                        break;
                default:
                        die("You didn't set product id in app settings.");
    }

    $balance = $day . '|' . $week . '|' . $month . '|' . $threemonth . '|' . $sixmonth . '|' . $lifetime . '|' . $year;
    // set balance
    misc\mysql\query("UPDATE `accounts` SET `balance` = ? WHERE `username` = ?", [$balance, $un]);
    die("Success: Reseller Balance Increased");
}

if (isset($_SERVER["HTTP_X_SHOPPY_SIGNATURE"]))
{
        $app = misc\etc\sanitize($_GET['app']);
        $query = misc\mysql\query("SELECT * FROM `apps` WHERE `secret` = ?", [$app]);
        
        if ($query->num_rows < 1)
        { // if no application was found with the supplied secret as the 'app' paramater
                die("Failure: application not found");
        }
        
        $row = mysqli_fetch_array($query->result);
        $name = $row["name"];
        $secret = $row["shoppysecret"];
        $dayproduct = $row["shoppydayproduct"];
        $weekproduct = $row["shoppyweekproduct"];
        $monthproduct = $row["shoppymonthproduct"];
        $lifetimeproduct = $row["shoppylifetimeproduct"];
        
        $payload = file_get_contents('php://input');
        $header_signature = misc\etc\sanitize($_SERVER["HTTP_X_SHOPPY_SIGNATURE"]);
        $signature = hash_hmac('sha512', $payload, $secret);
        if (!hash_equals($signature, $header_signature))
        { 
                // if the shoppy webhook secret the request was sent from didn't match the one set in the database
                die("Failure: authentication with shoppy secret failed");
        }
        $json = json_decode($payload);
        $un = misc\etc\sanitize($json->data->order->custom_fields[0]->value);
        
        $productid = misc\etc\sanitize($json->data->order->product_id);
        
        $query = misc\mysql\query("SELECT `balance` FROM `accounts` WHERE `username` = ? AND `app` = ?", [$un, $name]);
        
        if ($query->num_rows < 1)
        { 
                // if reseller not found
                die("Failure: No account with the supplied username under this application found");
        }
        // getting the balance of each key length for the specified reseller account
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
        
        $amount = misc\etc\sanitize($json->data->order->quantity); // find quantity of keys purchased
        // then given the duration of keys they purchased, add to their balance
        
        if(isset($_GET['expiry'])) {
                $expiry = misc\etc\sanitize($_GET['expiry']);
                $level = misc\etc\sanitize($_GET['level']);
                
                $key = misc\license\createLicense($amount, "******-******-******-******-******-******", $expiry, $level, NULL, 86400, $app, $un);
                
                $keys = NULL;
                for ($i = 0; $i < count($key); $i++) {
                        $keys .= "" . $key[$i] . "\n";
                }
                $keys = preg_replace(

                        '~[\r\n]+~',

                        "\r\n",

                        trim($keys)
                );
                die($keys);
        }
        
        switch ($productid)
        {
                case $dayproduct:
                        $day = $day + $amount;
                        break;
                case $weekproduct:
                        $week = $week + $amount;
                        break;
                case $monthproduct:
                        $month = $month + $amount;
                        break;
                case $lifetimeproduct:
                        $lifetime = $lifetime + $amount;
                        break;
                default:
                        die("You didn't set product id in app settings.");
        }
        
        $balance = $day . '|' . $week . '|' . $month . '|' . $threemonth . '|' . $sixmonth . '|' . $lifetime . '|' . $year;
        // set balance
        misc\mysql\query("UPDATE `accounts` SET `balance` = ? WHERE `username` = ?", [$balance, $un]);
        die("Success: Reseller Balance Increased");
}

if (isset($_SERVER["HTTP_SIGNATURE"]))
{
        $app = misc\etc\sanitize($_GET['app']);
        $query = misc\mysql\query("SELECT * FROM `apps` WHERE `secret` = ?", [$app]);
        
        if ($query->num_rows < 1)
        { 
                // if no application was found with the supplied secret as the 'app' paramater
                die("Failure: application not found");
        }
        
        $row = mysqli_fetch_array($query->result);
        $name = $row["name"];
        $secret = $row["sellappsecret"];
        $dayproduct = $row["sellappdayproduct"];
        $weekproduct = $row["sellappweekproduct"];
        $monthproduct = $row["sellappmonthproduct"];
        $lifetimeproduct = $row["sellapplifetimeproduct"];
        
        $payload = file_get_contents('php://input');
        
        $header_signature = misc\etc\sanitize($_SERVER["HTTP_SIGNATURE"]);
        $signature = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($signature, $header_signature))
        { 
                // if the SellApp webhook secret the request was sent from didn't match the one set in the database
                die("Failure: authentication with SellApp secret failed");
        }
        $json = json_decode($payload);
        $un = misc\etc\sanitize($json->additional_information[0]->value);
        
        $product = misc\etc\sanitize($json->listing->slug);
        
        $query = misc\mysql\query("SELECT `balance` FROM `accounts` WHERE `username` = ? AND `app` = ?", [$un, $name]);
        
        if ($query->num_rows < 1)
        { 
                // if reseller not found
                die("Failure: No account with the supplied username under this application found");
        }
        // getting the balance of each key length for the specified reseller account
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
        
        $amount = misc\etc\sanitize($json->quantity); // find quantity of keys purchased
        // then given the duration of keys they purchased, add to their balance
        
        if(isset($_GET['expiry'])) {
                $expiry = misc\etc\sanitize($_GET['expiry']);
                $level = misc\etc\sanitize($_GET['level']);
                
                $key = misc\license\createLicense($amount, "******-******-******-******-******-******", $expiry, $level, NULL, 86400, $app, $un);
                
                $keys = NULL;
                for ($i = 0; $i < count($key); $i++) {
                        $keys .= "" . $key[$i] . "\n";
                }
                $keys = preg_replace(

                        '~[\r\n]+~',

                        "\r\n",

                        trim($keys)
                );
                die($keys);
        }
        
        switch(true) {
                case stristr($dayproduct, $product):
                        $day = $day + $amount;
                        break;
                case stristr($weekproduct, $product):
                        $week = $week + $amount;
                        break;
                case stristr($monthproduct, $product):
                        $month = $month + $amount;
                        break;
                case stristr($lifetimeproduct, $product):
                        $lifetime = $lifetime + $amount;
                        break;
                default:
                        die("You didn't set product URL in app settings.");
        }
        
        $balance = $day . '|' . $week . '|' . $month . '|' . $threemonth . '|' . $sixmonth . '|' . $lifetime . '|' . $year;
        // set balance
        misc\mysql\query("UPDATE `accounts` SET `balance` = ? WHERE `username` = ?", [$balance, $un]);
        die("Success: Reseller Balance Increased");
}

?>