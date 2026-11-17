<?php
// Shared database connection. Settings come from config_local.php.
$configFile = __DIR__ . '/config_local.php';
if (!file_exists($configFile)) {
    die('Missing config_local.php. Copy config_example.php to config_local.php and edit it.');
}
$config = require $configFile;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['name'], $config['port']);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Sorry, the system is unavailable right now. Please try again later.');
}