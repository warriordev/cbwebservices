<?php
require_once __DIR__ . '/common.php';
$auth_key = NULL;
if (isset($_POST["user_id"]) && $_POST["user_id"] != "") {
    $qry = "SELECT * FROM oauth_access_tokens WHERE user_id=" . $_POST["user_id"] . " ORDER BY access_token DESC";
    $getToken = db:: getRecord($qry);
    $auth_key = $getToken["access_token"];
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>cbwebservice</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/base/jquery-ui.css" type="text/css" media="all"/>
        <link rel="stylesheet" type="text/css" href="vendor/DataTables/datatables.min.css"/>
        <style>
            body{font-family: arial;}
            .hide{
                display: none;
            }
            .cap-range{
                display: inline;
            }
            .range-slider{
                width: 300px;
            }
            .msg{
                text-align: center;
                font-weight: bold;
                color: red;
            }
            .search-filters{
                display: inline-block;
                float: left;
                margin: 10px;
            }
        </style>
    </head>
    <body>
        <?php if (isset($_POST["user_id"]) && $_POST["user_id"]) { ?>
            <div class="msg"></div>
            <div class="search-filters">
                <h3>Search by specification</h3>
                <form action="api.php" method="post">
                    <label for="part_number">Part Number:</label>
                    <input type="text" id="part_number" placeholder="Part Number"/>
                    <label for="voltage">Voltage:</label>
                    <select id="voltage">
                        <option value="0">Select</option>
                    </select>
                    <label for="weight">Weight:</label>
                    <select id="weight">
                        <option value="0">Select</option>
                    </select><br><br>
                    <p class="cap-range">
                        <label for="amount">Capacity range:</label>
                        <input type="text" id="amount" readonly style="border:0; color:#f6931f; font-weight:bold;">
                    </p><br>
                    <div class="range-slider">
                        <div id="slider-range"></div>
                        <div style="clear: both;"></div>    
                    </div>
                    <input type="hidden" id="max_capacity" value=""/>
                    <input type="hidden" id="min_capacity" value=""/>
                    <br><br>
                    <input type="hidden" name="method" value="searchbyspecification"/>
                    <input type="hidden" name="auth_key" value="<?php echo $auth_key; ?>"/>
                    <button type="button" id="get_search" value="Search">Search</button>
                    <span class="loader"></span>
                </form>
            </div>
            <div class="search-filters">
                <h3>Search by manufacturer</h3>
                <select id="manufacturer">
                    <option value="0">Select</option>
                </select>
                <select id="model">
                    <option value="0">Select</option>
                </select>
            </div>
            <br><br>
            <div style="clear: both;"></div>
            <div>
                <h3>Search Result</h3>
                <table id="search_result" class="search_result display" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>Battery Id</th>
                            <th>Part Number</th>
                            <th>Description</th>
                            <th>Image</th>
                            <th>Voltage</th>
                            <th>Capacity</th>
                            <th>Max Weight</th>
                        </tr>
                    </thead>
                </table>
            </div>
        <?php } else { ?>
            <!--            <a href="javascript:void(0)" onclick="confirmation()">Login</a>-->
            Incorrect username or password.
        <?php } ?>

        <script src="vendor/jquery-3.2.1.min.js" type="text/javascript"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <script type="text/javascript" src="vendor/DataTables/datatables.min.js"></script>
        <script type="text/javascript">

            $(function () {
                $("#slider-range").slider({
                    range: true,
                    min: 0,
                    max: 100,
                    values: [0, 0],
                    slide: function (event, ui) {
                        $("#amount").val(ui.values[ 0 ] + " - " + ui.values[ 1 ]);
                        $("#max_capacity").val(ui.values[ 1 ]);
                        $("#min_capacity").val(ui.values[ 0 ]);
                    }
                });
                $("#amount").val($("#slider-range").slider("values", 0) + " - " + $("#slider-range").slider("values", 1));
            });
            var table;
            $("document").ready(function () {
                var auth_key = '<?php echo $auth_key; ?>';
                table = $('#search_result').DataTable();
                $.getJSON("api.php?method=getaircrafts&auth_key=" + auth_key, function (result) {
                    var JSON_data = JSON.stringify(result);
                    var data = JSON.parse(JSON_data);
                    $.each(data.manufacturers, function (key, val) {
                        if (val.VehicleType_ID == 1) {
                            $("#manufacturer").append($('<option></option>').val(val.Manufacturer_ID).html(val.Manufacturer));
                        }
                    });
                });
                $("#manufacturer").on("change", function () {
                    var mId = $(this).val();
                    if (mId == 0) {
                        $("#model").empty();
                        $("#model").append($('<option></option>').val(0).html("Select"));
                    } else {
                        $.getJSON("api.php?method=getaircrafts&auth_key=" + auth_key + "&mId=" + mId, function (result) {
                            var JSON_data = JSON.stringify(result);
                            var data = JSON.parse(JSON_data);
                            $.each(data.manufacturers, function (key, val) {
                                $("#model").append($('<option></option>').val(val.VehicleType_ID).html(val.Model[0].Model));
                            });
                        });
                    }
                });
                $("#model").on("change", function () {
                    var vId = $(this).val();
                    if (vId != 0 && vId != "") {
                        $.getJSON("api.php?method=searchbyaircraft&auth_key=" + auth_key + "&vId=" + vId, function (result) {
                            table.destroy();
                            var JSON_data = JSON.stringify(result);
                            var data = JSON.parse(JSON_data);
                            $.each(data, function (key, val) {
                                if (key == 500 || key == 400 || key == 401) {
                                    $(".msg").html(val);
                                } else {
                                    table = $('#search_result').DataTable({
                                        "aaData": data.batteries,
                                        "aoColumns": [
                                            {"mDataProp": "Battery_ID"},
                                            {"mDataProp": "Part_Number"},
                                            {"mDataProp": "Description"},
                                            {"mDataProp": "Image"},
                                            {"mDataProp": "Voltage"},
                                            {"mDataProp": "Capacity"},
                                            {"mDataProp": "Maxweight"}
                                        ]
                                    });
                                }
                            });
                        });
                    }
                });
                // get search specs
                $.getJSON("api.php?method=getsearchspecifications&auth_key=" + auth_key, function (result) {
                    var JSON_data = JSON.stringify(result);
                    var data = JSON.parse(JSON_data);
                    $.each(data.specifications, function (i, fields) {
                        //volt
                        if (fields.Specification == "Voltage") {
                            $.each(fields.Values, function (key, val) {
                                $("#voltage").append($('<option></option>').val(val).html(val));
                            });
                        }
                        //weight
                        if (fields.Specification == "Weight") {
                            $.each(fields.Values, function (key, val) {
                                $("#weight").append($('<option></option>').val(val).html(val));
                            });
                        }
                    });
                });
                $("#get_search").on("click", function () {
                    var volt, weight, part_no;
                    //part number
                    var part_number = $("#part_number").val();
                    if (part_number == "") {
                        part_no = "";
                    } else {
                        part_no = part_number;
                    }
                    //voltage
                    var voltage = $("#voltage").val();
                    if (voltage == 0) {
                        volt = "";
                    } else {
                        volt = voltage;
                    }
                    //weight
                    var att_weight = $("#weight").val();
                    if (att_weight == 0) {
                        weight = "";
                    } else {
                        weight = att_weight;
                    }
                    //capacity
                    var max_capacity = $("#max_capacity").val();
                    var min_capacity = $("#min_capacity").val();
                    $.ajax({
                        type: 'POST',
                        url: 'api.php?method=searchbyspecification',
                        dataType: 'json',
                        data: {
                            volt: volt,
                            weight: weight,
                            part_no: part_no,
                            auth_key: auth_key,
                            max_capacity: max_capacity,
                            min_capacity: min_capacity
                        },
                        beforeSend: function (xhr) {
                            table.destroy();
                            $(".loader").text("Loading...");
                        },
                        success: function (res) {
                            var JSON_data = JSON.stringify(res);
                            var data = JSON.parse(JSON_data);
                            $.each(data, function (key, val) {
                                if (key == 500 || key == 400 || key == 401) {
                                    $(".msg").html(val);
                                    //table.fnClearTable();
                                    //table.fnDraw();
                                    //table.fnDestroy();
                                } else {
                                    table = $('#search_result').DataTable({
                                        "aaData": data.batteries,
                                        "aoColumns": [
                                            {"mDataProp": "Battery_id"},
                                            {"mDataProp": "Part_Number"},
                                            {"mDataProp": "Description"},
                                            {"mDataProp": "Image"},
                                            {"mDataProp": "Voltage"},
                                            {"mDataProp": "Capacity"},
                                            {"mDataProp": "Maxweight"}
                                        ]
                                    });
                                }
                            });
                        },
                        complete: function () {
                            $(".loader").text(" ");
                        }
                    });
                });
            });
            function confirmation() {
                var status = confirm("Allow API access?");
                if (status) {
                    window.location.href = '<?php echo $site_path . $site_root . "login.php"; ?>';
                }
            }
        </script>
    </body>
</html>