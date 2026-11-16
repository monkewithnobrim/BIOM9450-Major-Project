<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli('localhost', 'root', '', 'medication_db', 3307);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Sorry, the system is unavailable right now. Please try again later.');
}