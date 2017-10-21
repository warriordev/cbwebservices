<?php

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/server.php';

$error = array();
$flag = FALSE;
$method = $result = $username = $password = $client_id = NULL;

if (isset($_REQUEST["method"]) && $_REQUEST["method"] != "") {
    $method = $_REQUEST["method"];
}
switch ($method) {
    case "login":
        if (isset($_POST["client_id"]) && $_POST["client_id"] != "") {
            $client_id = $_POST["client_id"];
            if ($_REQUEST["username"] == "" && $_REQUEST["password"] == "") {
                $error["400"] = "Please provide username.";
                $flag = TRUE;
            } else {
                if (isset($_REQUEST["username"]) && $_REQUEST["username"] != "") {
                    $username = $_REQUEST["username"];
                } else {
                    $error["400"] = "Please provide username.";
                    $flag = TRUE;
                }
                if (isset($_POST["password"]) && $_POST["password"] != "") {
                    $password = $_POST["password"];
                } else {
                    $error["400"] = "Please provide password.";
                    $flag = TRUE;
                }
            }
        } else {
            $error["403"] = "API key is missing.";
            $flag = TRUE;
        }

        if ($flag == FALSE) {
            $query = "SELECT * FROM oauth_users WHERE username='" . $username . "' AND password='" . sha1($password) . "'";
            $user = db::getRecord($query);
            if ($user) {
                $params = array(
                    'grant_type' => $grant_type,
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'state' => $random_str
                );
                $res = http_call($token_url, $params);
                $auth = json_decode($res, TRUE);
                if ($auth) {
                    if (isset($auth["access_token"]) && $auth["access_token"] != "") {
                        $result = array("user_id" => $user["user_id"]);
                    } else {
                        $error["401"] = "Invalid API key.";
                        $flag = TRUE;
                    }
                } else {
                    $error["500"] = "Something went wrong. Please try again later.";
                    $flag = TRUE;
                }
            } else {
                $error["401"] = "Incorrect username or password.";
                $flag = TRUE;
            }
        }
        if ($flag == FALSE) {
            echo json_encode($result);
        } else {
            echo json_encode($error);
        }
        break;
    case "getaircrafts": //Get Aircrafts
        //tblmanufacturer, tblvehicle, tblvehicletype
        $where = NULL;
        if (isset($_GET["mId"]) && $_GET["mId"] != "") {
            $where = "WHERE tblmanufacturer.Manufacturer_ID=" . $_GET["mId"];
        }
        $manuf_qry = "SELECT tblmanufacturer.Manufacturer, tblmanufacturer.Manufacturer_ID,
                    tblvehicle.VechicleType_ID, tblvehicle.Model, tblvehicle.Vechicle_ID
                    FROM tblmanufacturer INNER JOIN tblvehicle ON tblmanufacturer.Manufacturer_ID=tblvehicle.Manufacturer_ID
                    $where";
        $getManuf = db::getRecords($manuf_qry);
        if ($getManuf) {
            $arr_manufacturers = $arr_manufacturer_id = $arr_manufacturer = $arr_model = $arr_vehicleType_id = $arr_vehicle = array();
            foreach ($getManuf as $row) {
                $arr_manufacturer_id[] = $row["Manufacturer_ID"];
                $arr_manufacturer[] = $row["Manufacturer"];
                $arr_model[] = array(
                    "Model_id" => $row["Vechicle_ID"],
                    "Model" => $row["Model"]
                );
                $vehicleType_qry = "SELECT * FROM tblvehicletype WHERE VehicleType_ID=" . $row["Manufacturer_ID"];
                $getVehicleType = db::getRecord($vehicleType_qry);
                $arr_vehicleType_id[] = $getVehicleType["VehicleType_ID"];
                $arr_vehicle[] = $getVehicleType["VehicleType"];
            }

            $arr_manuf = array(
                "Manufacturer_ID" => implode(" ", array_unique($arr_manufacturer_id)),
                "Manufacturer" => implode(" ", array_unique($arr_manufacturer)),
                "VehicleType_ID" => implode(" ", array_unique($arr_vehicleType_id)),
                "Vehicle" => implode(" ", array_unique($arr_vehicle)),
                "Model" => $arr_model
            );
            array_push($arr_manufacturers, $arr_manuf);
            $result = array("manufacturers" => $arr_manufacturers);
        } else {
            $error["500"] = "Something went wrong. Please try again later.";
            $flag = TRUE;
        }
        if ($flag == FALSE) {
            echo json_encode($result);
        } else {
            echo json_encode($error);
        }
        break;
    case "getsearchspecifications":
        $arr_search_specs = array();
        #volt
        $arr_volt_val = array();
        $volt_qry = "SELECT DISTINCT tblbatteryvoltage.Voltage_ID, tblvoltage.Voltage FROM tblvoltage INNER JOIN tblbatteryvoltage ON tblvoltage.Voltage_ID=tblbatteryvoltage.Voltage_ID";
        $getVoltAttr = db::getRecords($volt_qry);
        foreach ($getVoltAttr as $volt) {
            array_push($arr_volt_val, $volt["Voltage"]);
        }
        $arr_volt_specs = array(
            "Specification_id" => "1",
            "Specification" => "Voltage",
            "Values" => $arr_volt_val
        );
        array_push($arr_search_specs, $arr_volt_specs);

        #capacity
        $arr_capacity_specs_1 = $arr_capacity_id_1 = $arr_capacity_type_1 = $arr_capacity_val_1 = array();
        $cap1_qry = "SELECT DISTINCT tblcapacitytype.CapacityType, tblcapacitytype.CapacityType_ID, tblbatterycapacity.Capacity FROM tblcapacitytype INNER JOIN tblbatterycapacity ON tblcapacitytype.CapacityType_ID=tblbatterycapacity.CapacityType_ID WHERE tblbatterycapacity.CapacityType_ID=1";
        $getCapAttr1 = db::getRecords($cap1_qry);
        if ($getCapAttr1) {
            foreach ($getCapAttr1 as $capAttr1) {
                $arr_capacity_id_1[] = $capAttr1["CapacityType_ID"];
                $arr_capacity_type_1[] = $capAttr1["CapacityType"];
                $arr_capacity_val_1[] = $capAttr1["Capacity"];
            }
            $arr_capacity_specs_1 = array(
                "Specification_id" => "2",
                "Specification" => implode(" ", array_unique($arr_capacity_type_1)),
                "Values" => $arr_capacity_val_1
            );
            array_push($arr_search_specs, $arr_capacity_specs_1);
        }

        $arr_capacity_specs_2 = $arr_capacity_id_2 = $arr_capacity_type_2 = $arr_capacity_val_2 = array();
        $cap2_qry = "SELECT DISTINCT tblcapacitytype.CapacityType, tblcapacitytype.CapacityType_ID, tblbatterycapacity.Capacity FROM tblcapacitytype INNER JOIN tblbatterycapacity ON tblcapacitytype.CapacityType_ID=tblbatterycapacity.CapacityType_ID WHERE tblbatterycapacity.CapacityType_ID=2";
        $getCapAttr2 = db::getRecords($cap2_qry);
        if ($getCapAttr2) {
            foreach ($getCapAttr2 as $capAttr2) {
                $arr_capacity_id_2[] = $capAttr2["CapacityType_ID"];
                $arr_capacity_type_2[] = $capAttr2["CapacityType"];
                $arr_capacity_val_2[] = $capAttr2["Capacity"];
            }
            $arr_capacity_specs_2 = array(
                "Specification_id" => "3",
                "Specification" => implode(" ", array_unique($arr_capacity_type_2)),
                "Values" => $arr_capacity_val_2
            );
            array_push($arr_search_specs, $arr_capacity_specs_2);
        }

        #attribute => weight
        $arr_weight_specs = $arr_weight_id = $arr_weight_type = $arr_weight_val = array();
        $weight_qry = "SELECT DISTINCT tblattributetype.AttributeType, tblattributetype.AttributeType_ID, tblbatteryattribute.AttributeValue FROM tblattributetype INNER JOIN tblbatteryattribute ON tblattributetype.AttributeType_ID=tblbatteryattribute.AttributeType_ID AND tblattributetype.AttributeType_ID=6";
        $getWeightAttr = db::getRecords($weight_qry);
        if ($getWeightAttr) {
            foreach ($getWeightAttr as $weightAttr) {
                $arr_weight_id[] = $weightAttr["AttributeType_ID"];
                $arr_weight_type[] = $weightAttr["AttributeType"];
                $arr_weight_val[] = $weightAttr["AttributeValue"];
            }
            $arr_weight_specs = array(
                "Specification_id" => "4",
                "Specification" => implode(" ", array_unique($arr_weight_type)),
                "Values" => $arr_weight_val
            );
            array_push($arr_search_specs, $arr_weight_specs);
        }

        //TempRatingType_ID => 4
        $arr_tmpType4_specs = $arr_tmpType4_id = $arr_tmpType4_type = $arr_tmpType4_val = array();
        $tmpType4_qry = "SELECT DISTINCT tbltempratingtype.TempRatingType, tbltempratingtype.TempRatingType_ID, tblbatterytemprating.TempRating FROM tbltempratingtype INNER JOIN tblbatterytemprating ON tbltempratingtype.TempRatingType_ID=tblbatterytemprating.TempRatingType_ID WHERE tbltempratingtype.TempRatingType_ID=4";
        $getTmpType4 = db::getRecords($tmpType4_qry);
        if ($getTmpType4) {
            foreach ($getTmpType4 as $tmpType4) {
                $arr_tmpType4_id[] = $tmpType4["TempRatingType_ID"];
                $arr_tmpType4_type[] = $tmpType4["TempRatingType"];
                $arr_tmpType4_val[] = $tmpType4["TempRating"];
            }
            $arr_tmpType4_specs = array(
                "Specification_id" => "5",
                "Specification" => html_entity_decode(implode(" ", array_unique($arr_tmpType4_type))),
                "Values" => $arr_tmpType4_val
            );
            array_push($arr_search_specs, $arr_tmpType4_specs);
        }
        //TempRatingType_ID => 5
        $arr_tmpType5_specs = $arr_tmpType5_id = $arr_tmpType5_type = $arr_tmpType5_val = array();
        $tmpType5_qry = "SELECT DISTINCT tbltempratingtype.TempRatingType, tbltempratingtype.TempRatingType_ID, tblbatterytemprating.TempRating FROM tbltempratingtype INNER JOIN tblbatterytemprating ON tbltempratingtype.TempRatingType_ID=tblbatterytemprating.TempRatingType_ID WHERE tbltempratingtype.TempRatingType_ID=5";
        $getTmpType5 = db::getRecords($tmpType5_qry);
        if ($getTmpType5) {
            foreach ($getTmpType5 as $tmpType5) {
                $arr_tmpType5_id[] = $tmpType5["TempRatingType_ID"];
                $arr_tmpType5_type[] = $tmpType5["TempRatingType"];
                $arr_tmpType5_val[] = $tmpType5["TempRating"];
            }
            $arr_tmpType5_specs = array(
                "Specification_id" => "6",
                "Specification" => html_entity_decode(implode(" ", array_unique($arr_tmpType5_type))),
                "Values" => $arr_tmpType5_val
            );
            array_push($arr_search_specs, $arr_tmpType5_specs);
        }
        //TempRatingType_ID => 6
        $arr_tmpType6_specs = $arr_tmpType6_id = $arr_tmpType6_type = $arr_tmpType6_val = array();
        $tmpType6_qry = "SELECT DISTINCT tbltempratingtype.TempRatingType, tbltempratingtype.TempRatingType_ID, tblbatterytemprating.TempRating FROM tbltempratingtype INNER JOIN tblbatterytemprating ON tbltempratingtype.TempRatingType_ID=tblbatterytemprating.TempRatingType_ID WHERE tbltempratingtype.TempRatingType_ID=6";
        $getTmpType6 = db::getRecords($tmpType6_qry);
        if ($getTmpType6) {
            foreach ($getTmpType6 as $tmpType6) {
                $arr_tmpType6_id[] = $tmpType6["TempRatingType_ID"];
                $arr_tmpType6_type[] = $tmpType6["TempRatingType"];
                $arr_tmpType6_val[] = $tmpType6["TempRating"];
            }
            $arr_tmpType6_specs = array(
                "Specification_id" => "7",
                "Specification" => html_entity_decode(implode(" ", array_unique($arr_tmpType6_type))),
                "Values" => $arr_tmpType6_val
            );
            array_push($arr_search_specs, $arr_tmpType6_specs);
        }
        //TempRatingType_ID => 7
        $arr_tmpType7_specs = $arr_tmpType7_id = $arr_tmpType7_type = $arr_tmpType7_val = array();
        $tmpType7_qry = "SELECT DISTINCT tbltempratingtype.TempRatingType, tbltempratingtype.TempRatingType_ID, tblbatterytemprating.TempRating FROM tbltempratingtype INNER JOIN tblbatterytemprating ON tbltempratingtype.TempRatingType_ID=tblbatterytemprating.TempRatingType_ID WHERE tbltempratingtype.TempRatingType_ID=7";
        $getTmpType7 = db::getRecords($tmpType7_qry);
        if ($getTmpType7) {
            foreach ($getTmpType7 as $tmpType7) {
                $arr_tmpType7_id[] = $tmpType7["TempRatingType_ID"];
                $arr_tmpType7_type[] = $tmpType7["TempRatingType"];
                $arr_tmpType7_val[] = $tmpType7["TempRating"];
            }
            $arr_tmpType7_specs = array(
                "Specification_id" => "8",
                "Specification" => html_entity_decode(implode(" ", array_unique($arr_tmpType7_type))),
                "Values" => $arr_tmpType7_val
            );
            array_push($arr_search_specs, $arr_tmpType7_specs);
        }
        //TempRatingType_ID => 8
        $arr_tmpType8_specs = $arr_tmpType8_id = $arr_tmpType8_type = $arr_tmpType8_val = array();
        $tmpType8_qry = "SELECT DISTINCT tbltempratingtype.TempRatingType, tbltempratingtype.TempRatingType_ID, tblbatterytemprating.TempRating FROM tbltempratingtype INNER JOIN tblbatterytemprating ON tbltempratingtype.TempRatingType_ID=tblbatterytemprating.TempRatingType_ID WHERE tbltempratingtype.TempRatingType_ID=8";
        $getTmpType8 = db::getRecords($tmpType8_qry);
        if ($getTmpType8) {
            foreach ($getTmpType8 as $tmpType8) {
                $arr_tmpType8_id[] = $tmpType8["TempRatingType_ID"];
                $arr_tmpType8_type[] = $tmpType8["TempRatingType"];
                $arr_tmpType8_val[] = $tmpType8["TempRating"];
            }
            $arr_tmpType8_specs = array(
                "Specification_id" => "9",
                "Specification" => html_entity_decode(implode(" ", array_unique($arr_tmpType8_type))),
                "Values" => $arr_tmpType8_val
            );
            array_push($arr_search_specs, $arr_tmpType8_specs);
        }
        //TempRatingType_ID => 9
        $arr_tmpType9_specs = $arr_tmpType9_id = $arr_tmpType9_type = $arr_tmpType9_val = array();
        $tmpType9_qry = "SELECT DISTINCT tbltempratingtype.TempRatingType, tbltempratingtype.TempRatingType_ID, tblbatterytemprating.TempRating FROM tbltempratingtype INNER JOIN tblbatterytemprating ON tbltempratingtype.TempRatingType_ID=tblbatterytemprating.TempRatingType_ID WHERE tbltempratingtype.TempRatingType_ID=9";
        $getTmpType9 = db::getRecords($tmpType9_qry);
        if ($getTmpType9) {
            foreach ($getTmpType9 as $tmpType9) {
                $arr_tmpType9_id[] = $tmpType9["TempRatingType_ID"];
                $arr_tmpType9_type[] = $tmpType9["TempRatingType"];
                $arr_tmpType9_val[] = $tmpType9["TempRating"];
            }
            $arr_tmpType9_specs = array(
                "Specification_id" => "10",
                "Specification" => html_entity_decode(implode(" ", array_unique($arr_tmpType9_type))),
                "Values" => $arr_tmpType9_val
            );
            array_push($arr_search_specs, $arr_tmpType9_specs);
        }

        #dimensions
        $arr_dimension_specs = $arr_dimension_val = $arr_dimension = array();
        $dimension_qry = "SELECT tblattributetype.AttributeType, tblbatteryattribute.Battery_ID, tblbatteryattribute.AttributeType_ID, tblbatteryattribute.AttributeValue 
                    FROM tblattributetype INNER JOIN tblbatteryattribute 
                    ON tblattributetype.AttributeType_ID=tblbatteryattribute.AttributeType_ID
                    WHERE tblbatteryattribute.AttributeType_ID=3 OR tblbatteryattribute.AttributeType_ID=4 OR tblbatteryattribute.AttributeType_ID=5";
        $getDimensions = db::getRecords($dimension_qry);
        if ($getDimensions) {
            foreach ($getDimensions as $dimension) {
                if ($dimension["AttributeType_ID"] == 3) { //length
                    $arr_dimension["L"] = $dimension["AttributeValue"];
                }
                if ($dimension["AttributeType_ID"] == 4) { //width
                    $arr_dimension["W"] = $dimension["AttributeValue"];
                }
                if ($dimension["AttributeType_ID"] == 5) { //height
                    $arr_dimension["H"] = $dimension["AttributeValue"];
                }
                array_push($arr_dimension_val, implode("x", $arr_dimension));
            }
            $arr_dimension_specs = array(
                "Specification_id" => "11",
                "Specification" => "Dimensions",
                "Values" => $arr_dimension_val
            );
            array_push($arr_search_specs, $arr_dimension_specs);
        }

        $result = array("specifications" => $arr_search_specs);

        if ($flag == FALSE) {
            echo json_encode($result);
        } else {
            echo json_encode($error);
        }
        break;
    case "searchbyaircraft":
        $where = NULL;
        if (isset($_GET["vId"]) && $_GET["vId"] != "") {
            $where = " WHERE Vechicle_ID=" . $_GET["vId"];
        }
        $bv_qry = "SELECT * FROM tblbatteryvechicle $where";
        $getBatteryVehicle = db::getRecords($bv_qry);
        if ($getBatteryVehicle) {
            $arr_search_craft = array();
            foreach ($getBatteryVehicle as $batteryVehicle) {
                $btry_qry = "SELECT * FROM tblbattery WHERE Battery_ID=" . $batteryVehicle["Battery_ID"];
                $batteries = db::getRecord($btry_qry);
                #battery detail
                $arr_detail = array(
                    "Battery_ID" => $batteries["Battery_ID"],
                    "Part_Number" => $batteries["Part_Number"],
                    "Description" => htmlentities(strip_tags($batteries["Description"]))
                );

                #media detail
                $media_qry = "SELECT * FROM tblmediatype INNER JOIN tblbatterymedia ON tblmediatype.MediaType_ID=tblbatterymedia.MediaType_ID AND Battery_ID=" . $batteries["Battery_ID"];
                $getMedia = db::getRecords($media_qry);
                foreach ($getMedia as $media) {
                    if ($media["MediaType"] == "PhotoImage") {
                        $arr_detail["Image"] = $media["FileName"];
                    }
                }

                #attribute Voltage
                $volt_qry = "SELECT * FROM tblvoltage INNER JOIN tblbatteryvoltage ON tblvoltage.Voltage_ID=tblbatteryvoltage.Voltage_ID WHERE tblbatteryvoltage.Battery_ID=" . $batteries["Battery_ID"];
                $getVolt = db::getRecord($volt_qry);
                if ($getVolt) {
                    $arr_detail["Voltage"] = $getVolt["Voltage"] . "v";
                }

                #attribute Capacity
                $capacity_qry = "SELECT * FROM tblcapacitytype INNER JOIN tblbatterycapacity ON tblcapacitytype.CapacityType_ID=tblbatterycapacity.CapacityType_ID WHERE tblbatterycapacity.Battery_ID=" . $batteries["Battery_ID"];
                $getCapacity = db::getRecord($capacity_qry);
                if ($getCapacity) {
                    $arr_detail["Capacity"] = $getCapacity["Capacity"];
                }

                #attribute Weight
                $attribute_qry = "SELECT * FROM tblattributetype INNER JOIN tblbatteryattribute ON tblattributetype.AttributeType_ID=tblbatteryattribute.AttributeType_ID WHERE tblbatteryattribute.Battery_ID=" . $batteries["Battery_ID"];
                $getAttr = db::getRecords($attribute_qry);
                foreach ($getAttr as $attr) {
                    if ($attr["AttributeType"] == "Weight") {
                        $arr_detail["Maxweight"] = $attr["AttributeValue"];
                    } else {
                        $arr_detail["Maxweight"] = "";
                    }
                }
                array_push($arr_search_craft, $arr_detail);
            }
        } else {
            $error["500"] = "Something went wrong. Please try again later.";
            $flag = TRUE;
        }

        if ($flag == FALSE) {
            echo json_encode(array("batteries" => $arr_search_craft));
        } else {
            echo json_encode($error);
        }
        break;
    case "searchbyspecification":
        /**
         * tblbattery
         * tblvoltage
         * tblbatteryvoltage
         * tblcapacitytype
         * tblbatterycapacity
         * tblbatteryattribute
         * tblattributetype
         * tblbatterymedia
         * tblmediatype
         * tblbatterytemprating
         * tbltempratingtype
         * tblapprovaltype
         * tblbatteryapproval
         * 
         * * */
        $arr_search_batteries = $arr_qry = array();
        if (isset($_POST["volt"]) && $_POST["volt"] != "") {
            $arr_qry[] = "tblvoltage.Voltage LIKE '%" . $_POST["volt"] . "%'";
        }

        if (isset($_POST["weight"]) && $_POST["weight"] != "") {
            $arr_qry[] = "tblbatteryattribute.AttributeValue LIKE '%" . $_POST["weight"] . "%'";
        }

        if (isset($_POST["part_no"]) && $_POST["part_no"] != "") {
            $arr_qry[] = "tblbattery.Part_Number LIKE '%" . $_POST["part_no"] . "%'";
        }

        if (isset($_POST["min_capacity"]) && isset($_POST["max_capacity"]) && $_POST["max_capacity"] !="") {
            if($_POST["min_capacity"] == 0 && $_POST["max_capacity"] > 0){
                $arr_qry[] = "tblbatterycapacity.Capacity >'" . $_POST["min_capacity"] . "'" . " AND tblbatterycapacity.Capacity <='" . $_POST["max_capacity"] . "'";
            }else{
               $arr_qry[] = "tblbatterycapacity.Capacity >='" . $_POST["min_capacity"] . "'" . " AND tblbatterycapacity.Capacity <='" . $_POST["max_capacity"] . "'"; 
            }  
        }
        
        if ($arr_qry) {
            db::executeQry("SET SQL_BIG_SELECTS=1");
            $str_qry = implode(" AND ", $arr_qry);
            $battries_qry = "SELECT tblbattery.Battery_ID,tblbattery.Part_Number,tblbattery.Description,
            tblbatteryvoltage.Voltage_ID,
            tblvoltage.Voltage,
            tblbatterycapacity.Capacity,
            tblcapacitytype.CapacityType_ID, tblcapacitytype.CapacityType,
            tblbatteryattribute.AttributeType_ID, tblbatteryattribute.AttributeValue,
            tblattributetype.AttributeType,
            tblbatterymedia.MediaType_ID, tblbatterymedia.FileName,
            tblmediatype.MediaType_ID, tblmediatype.MediaType
            FROM tblbattery
            INNER JOIN tblbatteryvoltage ON tblbattery.Battery_ID=tblbatteryvoltage.Battery_ID
            INNER JOIN tblvoltage ON tblbatteryvoltage.Voltage_ID=tblvoltage.Voltage_ID
            INNER JOIN tblbatterycapacity ON tblbatterycapacity.Battery_ID=tblbattery.Battery_ID
            INNER JOIN tblcapacitytype ON tblcapacitytype.CapacityType_ID=tblbatterycapacity.CapacityType_ID
            INNER JOIN tblbatteryattribute ON tblbatteryattribute.Battery_ID=tblbattery.Battery_ID
            INNER JOIN tblattributetype ON tblbatteryattribute.AttributeType_ID=tblattributetype.AttributeType_ID
            INNER JOIN tblbatterymedia ON tblbatterymedia.Battery_ID=tblbattery.Battery_ID
            INNER JOIN tblmediatype ON tblbatterymedia.MediaType_ID=tblmediatype.MediaType_ID
            WHERE $str_qry";

            $getBatteries = db::getRecords($battries_qry);
            if ($getBatteries) {
                foreach ($getBatteries as $battery) {
                    $arr_detail = array(
                        "Battery_id" => $battery["Battery_ID"],
                        "Part_Number" => $battery["Part_Number"],
                        "Description" => htmlentities(strip_tags($battery["Description"])),
                        "Voltage" => $battery["Voltage"],
                        "Capacity" => $battery["Capacity"]
                    );

                    if ($battery["MediaType"] == "PhotoImage") {
                        $arr_detail["Image"] = $battery["FileName"];
                    } else {
                        $arr_detail["Image"] = "";
                    }
                    if ($battery["AttributeType"] == "Weight") {
                        $arr_detail["Maxweight"] = $battery["AttributeValue"];
                    } else {
                        $arr_detail["Maxweight"] = "";
                    }
                    array_push($arr_search_batteries, $arr_detail);
                }
            } else {
                $error["500"] = "Something went wrong. Please try again later.";
                $flag = TRUE;
            }
        } else {
            $error["400"] = "Invalid search criteria.";
            $flag = TRUE;
        }

        if ($flag == FALSE) {
            echo json_encode(array("batteries" => $arr_search_batteries));
        } else {
            echo json_encode($error);
        }
        break;
    case "getbatterydetails"://Get battery details
        $battery_qry = "SELECT * FROM tblbattery";
        $getBatteries = db::getRecords($battery_qry);
        $arr_batteries = array();
        if ($getBatteries) {
            foreach ($getBatteries as $batteries) {
                #battery detail
                $arr_detail = array(
                    "Battery_ID" => $batteries["Battery_ID"],
                    "Part_Number" => $batteries["Part_Number"],
                    "Description" => $batteries["Description"]
                );

                #media detail
                $media_qry = "SELECT * FROM tblmediatype INNER JOIN tblbatterymedia ON tblmediatype.MediaType_ID=tblbatterymedia.MediaType_ID WHERE tblbatterymedia.Battery_ID=" . $batteries["Battery_ID"];
                $getMedia = db::getRecords($media_qry);
                $arr_docs = array();
                foreach ($getMedia as $media) {
                    if ($media["MediaType_ID"] == 2) { //PhotoImage
                        $arr_detail["Image"] = $media["FileName"];
                    }
                    if ($media["MediaType_ID"] == 3) { //FootprintImage
                        $arr_detail["Footprint"] = $media["FileName"];
                    }
                    if ($media["MediaType_ID"] == 14) { //OutlineDrawing
                        $arr_detail["OutlineDrawing"] = $media["FileName"];
                    }

                    #documents media
                    if ($media["MediaType_ID"] == 9) { //SDS
                        $arr_docs["Datasheet"] = $media["FileName"];
                    }
                    if ($media["MediaType_ID"] == 5) { //CMM
                        $arr_docs["CMM-ICA"] = $media["FileName"];
                    }
                    if ($media["MediaType_ID"] == 13) { //OOM
                        $arr_docs["OOM"] = $media["FileName"];
                    }
                    if ($media["MediaType_ID"] == 9) { //SDS
                        $arr_docs["SDS"] = $media["FileName"];
                    }
                    if ($media["MediaType_ID"] == 4) { //TSOLetter
                        $arr_docs["TSOLetter"] = $media["FileName"];
                    }
                    if ($media["MediaType_ID"] == 11) { //DDP
                        $arr_docs["DDP"] = $media["FileName"];
                    }
                }

                #specifications
                $voltage_qry = "SELECT * FROM tblvoltage INNER JOIN tblbatteryvoltage ON tblvoltage.Voltage_ID=tblbatteryvoltage.Voltage_ID AND tblbatteryvoltage.Battery_ID=" . $batteries["Battery_ID"];
                $getVoltage = db::getRecord($voltage_qry);
                $capacity_qry = "SELECT * FROM tblcapacitytype INNER JOIN tblbatterycapacity ON tblcapacitytype.CapacityType_ID=tblbatterycapacity.CapacityType_ID AND tblbatterycapacity.Battery_ID=" . $batteries["Battery_ID"];
                $getCapacity = db::getRecord($capacity_qry);
                $arr_specifications = array(
                    "Voltage" => $getVoltage["Voltage"] . "v",
                    "Capacity" => $getCapacity["Capacity"]
                );

                #TSO_approved 
                $tso_qry = "SELECT * FROM tblapprovaltype INNER JOIN tblbatteryapproval ON tblapprovaltype.ApprovalType_ID=tblbatteryapproval.ApprovalType_ID WHERE tblbatteryapproval.Battery_ID=" . $batteries["Battery_ID"];
                $getTSO = db::getRecord($tso_qry);
                if ($getTSO) {
                    $arr_specifications["tsoapproved"] = "Yes";
                } else {
                    $arr_specifications["tsoapproved"] = "No";
                }

                $arr_specs = array("Specifications" => $arr_specifications);

                #get attributes
                $attributes_qry = "SELECT * FROM tblattributetype INNER JOIN tblbatteryattribute ON tblattributetype.AttributeType_ID=tblbatteryattribute.AttributeType_ID AND tblbatteryattribute.Battery_ID=" . $batteries["Battery_ID"];
                $getAttr = db::getRecords($attributes_qry);
                foreach ($getAttr as $attr) {
                    if ($attr["AttributeType"] == "Weight") {
                        $arr_specs["Specifications"]["Maxweight"] = $attr["AttributeValue"];
                    } else {
                        $arr_specs["Specifications"]["Maxweight"] = "No";
                    }
                    if ($attr["AttributeType"] == "Heated") {
                        $arr_specs["Specifications"]["Heated"] = $attr["AttributeValue"];
                    } else {
                        $arr_specs["Specifications"]["Heated"] = "No";
                    }
                    if ($attr["AttributeType"] == "Engine Starting") {
                        $arr_specs["Specifications"]["EngineStarting"] = $attr["AttributeValue"];
                    } else {
                        $arr_specs["Specifications"]["EngineStarting"] = "No";
                    }
                    if ($attr["AttributeType"] == "Weight") {
                        $arr_specs["Specifications"]["Weight"] = $attr["AttributeValue"];
                    } else {
                        $arr_specs["Specifications"]["Weight"] = "";
                    }
                }

                $tmptype_qry = "SELECT * FROM tbltempratingtype INNER JOIN tblbatterytemprating ON tbltempratingtype.TempRatingType_ID=tblbatterytemprating.TempRatingType_ID WHERE tblbatterytemprating.Battery_ID=" . $batteries["Battery_ID"];
                $getTmpType = db::getRecords($tmptype_qry);
                $arr_ipp = $arr_ipr = array();
                if ($getTmpType) {
                    foreach ($getTmpType as $tmptype) {
                        if ($tmptype["TempRatingType_ID"] == 4 || $tmptype["TempRatingType_ID"] == 6 || $tmptype["TempRatingType_ID"] == 8) {
                            array_push($arr_ipp, $tmptype["TempRatingType"]);
                        }
                        if ($tmptype["TempRatingType_ID"] == 5 || $tmptype["TempRatingType_ID"] == 7 || $tmptype["TempRatingType_ID"] == 9) {
                            array_push($arr_ipr, $tmptype["TempRatingType"]);
                        }
                    }
                }
                $arr_specs["Specifications"]["IPP"] = $arr_ipp;
                $arr_specs["Specifications"]["IPR"] = $arr_ipr;

                $arr_merge = array_merge($arr_detail, $arr_specs, array("Documents" => $arr_docs));
                array_push($arr_batteries, $arr_merge);
            }
        } else {
            $error["500"] = "Something went wrong. Please try again later.";
            $flag = TRUE;
        }
        if ($flag == FALSE) {
            echo json_encode($arr_batteries);
        } else {
            echo json_encode($error);
        }
        break;
    case "getbatterydetailbyid"://Get battery detail by id
        if (isset($_POST["battery_id"]) && $_POST["battery_id"] != "") {
            $chk_row = "SELECT Battery_ID FROM tblbattery WHERE Battery_ID=" . $_POST["battery_id"];
            $status = db::getRecord($chk_row);
            if ($status > 0) {
                $battery_qry = "SELECT * FROM tblbattery WHERE Battery_ID=" . $_POST["battery_id"];
                $getBatteries = db::getRecords($battery_qry);
                if ($getBatteries) {
                    $arr_batteries = array();
                    foreach ($getBatteries as $batteries) {
                        #battery detail
                        $arr_detail = array(
                            "Battery_ID" => $batteries["Battery_ID"],
                            "Part_Number" => $batteries["Part_Number"],
                            "Description" => $batteries["Description"]
                        );

                        #media detail
                        $media_qry = "SELECT * FROM tblmediatype INNER JOIN tblbatterymedia ON tblmediatype.MediaType_ID=tblbatterymedia.MediaType_ID WHERE tblbatterymedia.Battery_ID=" . $batteries["Battery_ID"];
                        $getMedia = db::getRecords($media_qry);
                        $arr_docs = array();
                        foreach ($getMedia as $media) {
                            if ($media["MediaType_ID"] == 2) { //PhotoImage
                                $arr_detail["Image"] = $media["FileName"];
                            }
                            if ($media["MediaType_ID"] == 3) { //FootprintImage
                                $arr_detail["Footprint"] = $media["FileName"];
                            }
                            if ($media["MediaType_ID"] == 14) { //OutlineDrawing
                                $arr_detail["OutlineDrawing"] = $media["FileName"];
                            }

                            #documents media
                            if ($media["MediaType_ID"] == 9) { //SDS
                                $arr_docs["Datasheet"] = $media["FileName"];
                            }
                            if ($media["MediaType_ID"] == 5) { //CMM
                                $arr_docs["CMM-ICA"] = $media["FileName"];
                            }
                            if ($media["MediaType_ID"] == 13) { //OOM
                                $arr_docs["OOM"] = $media["FileName"];
                            }
                            if ($media["MediaType_ID"] == 9) { //SDS
                                $arr_docs["SDS"] = $media["FileName"];
                            }
                            if ($media["MediaType_ID"] == 4) { //TSOLetter
                                $arr_docs["TSOLetter"] = $media["FileName"];
                            }
                            if ($media["MediaType_ID"] == 11) { //DDP
                                $arr_docs["DDP"] = $media["FileName"];
                            }
                        }

                        #specifications
                        $voltage_qry = "SELECT * FROM tblvoltage INNER JOIN tblbatteryvoltage ON tblvoltage.Voltage_ID=tblbatteryvoltage.Voltage_ID AND tblbatteryvoltage.Battery_ID=" . $batteries["Battery_ID"];
                        $getVoltage = db::getRecord($voltage_qry);
                        $capacity_qry = "SELECT * FROM tblcapacitytype INNER JOIN tblbatterycapacity ON tblcapacitytype.CapacityType_ID=tblbatterycapacity.CapacityType_ID AND tblbatterycapacity.Battery_ID=" . $batteries["Battery_ID"];
                        $getCapacity = db::getRecord($capacity_qry);
                        $arr_specifications = array(
                            "Voltage" => $getVoltage["Voltage"] . "v",
                            "Capacity" => $getCapacity["Capacity"]
                        );

                        #TSO_approved 
                        $tso_qry = "SELECT * FROM tblapprovaltype INNER JOIN tblbatteryapproval ON tblapprovaltype.ApprovalType_ID=tblbatteryapproval.ApprovalType_ID WHERE tblbatteryapproval.Battery_ID=" . $batteries["Battery_ID"];
                        $getTSO = db::getRecord($tso_qry);
                        if ($getTSO) {
                            $arr_specifications["tsoapproved"] = "Yes";
                        } else {
                            $arr_specifications["tsoapproved"] = "No";
                        }

                        $arr_specs = array("Specifications" => $arr_specifications);

                        #get attributes
                        $attributes_qry = "SELECT * FROM tblattributetype INNER JOIN tblbatteryattribute ON tblattributetype.AttributeType_ID=tblbatteryattribute.AttributeType_ID AND tblbatteryattribute.Battery_ID=" . $batteries["Battery_ID"];
                        $getAttr = db::getRecords($attributes_qry);
                        foreach ($getAttr as $attr) {
                            if ($attr["AttributeType"] == "Weight") {
                                $arr_specs["Specifications"]["Maxweight"] = $attr["AttributeValue"];
                            } else {
                                $arr_specs["Specifications"]["Maxweight"] = "No";
                            }
                            if ($attr["AttributeType"] == "Heated") {
                                $arr_specs["Specifications"]["Heated"] = $attr["AttributeValue"];
                            } else {
                                $arr_specs["Specifications"]["Heated"] = "No";
                            }
                            if ($attr["AttributeType"] == "Engine Starting") {
                                $arr_specs["Specifications"]["EngineStarting"] = $attr["AttributeValue"];
                            } else {
                                $arr_specs["Specifications"]["EngineStarting"] = "No";
                            }
                            if ($attr["AttributeType"] == "Weight") {
                                $arr_specs["Specifications"]["Weight"] = $attr["AttributeValue"];
                            } else {
                                $arr_specs["Specifications"]["Weight"] = "";
                            }
                        }

                        $tmptype_qry = "SELECT * FROM tbltempratingtype INNER JOIN tblbatterytemprating ON tbltempratingtype.TempRatingType_ID=tblbatterytemprating.TempRatingType_ID WHERE tblbatterytemprating.Battery_ID=" . $batteries["Battery_ID"];
                        $getTmpType = db::getRecords($tmptype_qry);
                        $arr_ipp = $arr_ipr = array();
                        if ($getTmpType) {
                            foreach ($getTmpType as $tmptype) {
                                if ($tmptype["TempRatingType_ID"] == 4 || $tmptype["TempRatingType_ID"] == 6 || $tmptype["TempRatingType_ID"] == 8) {
                                    array_push($arr_ipp, $tmptype["TempRatingType"]);
                                }
                                if ($tmptype["TempRatingType_ID"] == 5 || $tmptype["TempRatingType_ID"] == 7 || $tmptype["TempRatingType_ID"] == 9) {
                                    array_push($arr_ipr, $tmptype["TempRatingType"]);
                                }
                            }
                        }
                        $arr_specs["Specifications"]["IPP"] = $arr_ipp;
                        $arr_specs["Specifications"]["IPR"] = $arr_ipr;

                        $arr_merge = array_merge($arr_detail, $arr_specs, array("Documents" => $arr_docs));
                        array_push($arr_batteries, $arr_merge);
                    }
                } else {
                    $error["500"] = "Something went wrong. Please try again later.";
                    $flag = TRUE;
                }
            } else {
                $error["400"] = "Invalid battery_id.";
                $flag = TRUE;
            }
        } else {
            $error["400"] = "Please provide battery_id.";
            $flag = TRUE;
        }

        if ($flag == FALSE) {
            echo json_encode($arr_batteries);
        } else {
            echo json_encode($error);
        }
        break;
}