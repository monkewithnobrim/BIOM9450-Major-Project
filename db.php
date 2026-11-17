// Every page should start with "require 'db.php'" while changing the port and password to match their machine
//The try/catch is to show a friendly message which is required for the some of the validation marks
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli('localhost', 'root', '', 'medication_db', 3307);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Sorry, the system is unavailable right now. Please try again later.');
}