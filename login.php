<?php require_once __DIR__ . '/common.php';?>
<!DOCTYPE html>
<html>
    <head>
        <title>Login</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <form action="api.php" method="post">
            <table>
                <tr>
                    <td>Username</td>
                    <td><input type="text" name="username" placeholder="Username"/></td>
                </tr>
                <tr>
                    <td>Password</td>
                    <td>
                        <input type="password" name="password" placeholder="Password"/>
                        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>"/>
                    </td>
                    <td><input type="hidden" name="method" value="login"/></td>
                </tr>
                <tr>
                    <td><input type="submit" name="submit" value="Submit"/></td>
                </tr>
            </table>
        </form>
    </body>
</html>
