<?php

session_start();
ini_set('display_errors', 1);

$servers = array('localhost', '127.0.0.1', 'warrior');
if (in_array($_SERVER['HTTP_HOST'], $servers)) { //local
    date_default_timezone_set("Asia/Karachi");
    $site_path = "http://localhost";
    $site_root = "/service/";
    $site_title = 'Service';
} else { //live
    ini_set('memory_limit', '512M');
    date_default_timezone_set("Europe/London");
    $site_path = "http://microrage.com/projects";
    $site_root = "/service/";
    $site_title = 'Service';
}
#service params
$token_url = $site_path . $site_root . 'token.php';
$grant_type = "client_credentials";
$client_id = "testclient";
$client_secret = "testpass";
$random_str = mt_rand(10, 100000);
?>