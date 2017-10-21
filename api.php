<?php

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/server.php';

$error = array();
$flag = FALSE;
$method = $auth_key = $condition = $token = NULL;

if (isset($_REQUEST["method"]) && $_REQUEST["method"] != "") {
    $method = $_REQUEST["method"];
    if (isset($_REQUEST["auth_key"]) && $_REQUEST["auth_key"] != "") {
        $auth_key = $_REQUEST["auth_key"];
    }
    $req_url = $site_path . $site_root . "resource.php?method=" . $method;
    $token_url = $site_path . $site_root . "validate_token.php";

    if ($_REQUEST["method"] == "login") {
        $params = array(
            'username' => "warrior",
            'password' => "admin123",
            'client_id' => $client_id
        );
        echo $res = http_post($req_url, $params, $client_id);
        exit;
    }
    #check token
    if (!empty($auth_key)) {
        #validate token
        $params = array(
            'access_token' => $auth_key
        );
        $token_status = validate_token($token_url, $params);
        if (isset($token_status["error"]) && $token_status["error"] == "invalid_token") {
            $error["401"] = "Invalid API key.";
            $flag = TRUE;
        } else if (isset($token_status["error"]) && $token_status["error"] == "invalid_request") {
            $error["401"] = "Invalid request.";
            $flag = TRUE;
        } else {
            switch ($method) {
                case "getaircrafts": //Get Aircrafts
                    echo http_get($req_url, "Bearer " . $auth_key);
                    break;
                case "getsearchspecifications"://Get Search Specifications
                    echo http_get($req_url, "Bearer " . $auth_key);
                    break;
                case "getbatterydetails": //Get Battery Details
                    echo http_get($req_url, "Bearer " . $auth_key);
                    break;
                case "getbatterydetailbyid": //Get Battery Detail By Id
                    $params = array(
                        "battery_id" => $_POST["battery_id"],
                        'auth_key' => $auth_key
                    );
                    echo http_post($req_url, $params, "Bearer " . $auth_key);
                    break;
                case "searchbyaircraft": //Search By AirCraft
                    echo http_get($req_url, "Bearer " . $auth_key);
                    break;
                case "searchbyspecification": //Search By Specifications
                    $params = array(
                        "volt" => isset($_POST["volt"]) ? $_POST["volt"] : NULL,
                        "weight" => isset($_POST["weight"]) ? $_POST["weight"] : NULL,
                        "part_no" => isset($_POST["part_no"]) ? $_POST["part_no"] : NULL,
                        "max_capacity" => isset($_POST["max_capacity"]) ? $_POST["max_capacity"] : NULL,
                        "min_capacity" => isset($_POST["min_capacity"]) ? $_POST["min_capacity"] : NULL,
                        'auth_key' => $auth_key
                    );
                    echo http_post($req_url, $params, "Bearer " . $auth_key);
                    break;
            }
        }
    } else {
        $error["400"] = "Missing access token.";
        $flag = TRUE;
    }
} else {
    $error["500"] = "Something went wrong. Please try again later.";
    $flag = TRUE;
}

if ($flag == TRUE) {
    echo json_encode($error);
}
?>

