<?php

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/server.php';

if (!isset($_POST["j"]) && !isset($_GET['j'])) {
    header("location: " . $site_root . "index.php?m=901");
} else {
    $task = trim($_REQUEST['j']);
}

switch ($task) {
    case "logout": //Logout
        db::deleteRecord("oauth_access_tokens", "user_id", $_SESSION["user_id"]);
        session_unset();
        session_destroy();
        header("Location: " . $site_path . $site_root . "index.php");
        break;
}
?>