<?php require_once __DIR__ . '/common.php'; ?>
<!DOCTYPE html>
<html>
    <head>
        <title>Service</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <div id="msg"></div>

        <script src="vendor/jquery-3.2.1.min.js" type="text/javascript"></script>
        <script type="text/javascript">
            $("document").ready(function () {
                $.getJSON("api.php?method=login", function (result) {
                    var JSON_data = JSON.stringify(result);
                    var data = JSON.parse(JSON_data);
                    $.each(data, function (key, val) {
                        if (key == 400 || key == 403 || key == 401 || key == 500) {
                            $("#msg").html(val);
                        } else {
                            //console.log(data.user_id);
                            var url = 'search.php';
                            var form = $('<form action="' + url + '" method="post">' +
                                    '<input type="hidden" name="user_id" value="' + data.user_id + '" />' +
                                    '</form>');
                            $('body').append(form);
                            form.submit();
                        }
                    });
                });
            });
        </script>
    </body>
</html>