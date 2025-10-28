<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation</title>
</head>
<body>
    <h1>Application Installation</h1>
    <form action="scripts/install.php" method="post">
        <label for="db_host">Database Host:</label>
        <input type="text" id="db_host" name="db_host" required><br>

        <label for="db_name">Database Name:</label>
        <input type="text" id="db_name" name="db_name" required><br>

        <label for="db_user">Database User:</label>
        <input type="text" id="db_user" name="db_user" required><br>

        <label for="db_pass">Database Password:</label>
        <input type="password" id="db_pass" name="db_pass" required><br>

        <input type="submit" value="Install">
    </form>
</body>
</html>