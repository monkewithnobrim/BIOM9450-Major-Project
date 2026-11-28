<?php
// Shared database connection. Settings come from config_local.php.
$configFile = __DIR__ . '/config_local.php';
// checks if the config_local file is missing
if (!file_exists($configFile)) {
    // die() prints the message and stops the whole page straight away
    // message is for team so they know how to fix their setup
    die('Missing config_local.php. Copy config_example.php to config_local.php and edit it.');
}
// require loads and runs another file, this grabs the array of settings from the config_local file
$config = require $configFile;

// Tells mysqli how to report problems so a faile connection does not just print a PHP warning and carry on with a broken $conn
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// PHP runs the code in try and if an exception is thrown inside it then PHP jumps straight to the catch block
try {
    // new mysqli() opens a connection to the database server with the values inside the ()
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['name'], $config['port']);
    // sets the character set used for talking to the database to utf8mb4 which matches the tables 
    // and supports every letter (including accentsm apostrophes and emojis)
    $conn->set_charset('utf8mb4');
// catch runs only if the connection or set_charset threw a mysqli_sql_exception. $e holds the error details
} catch (mysqli_sql_exception $e) {
    // Shows the user a safe message and stops. Real error $e is not shown as it may reveal the server address
    // or username to a visitor
    die('Sorry, the system is unavailable right now. Please try again later.');
}