<?php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$pages = [
    '/',
    '/index.php',
    '/login.php',
    '/logout.php',
    '/dispensing.php',
    '/dispense_save.php',
    '/add_patient.php',
    '/patient_search.php',
    '/patient_edit.php',
    '/medication_report.php',
    '/style.css',
    '/script.js'
];

if (in_array($path, $pages, true)) {
    return false;
}

if (preg_match('~^/(photos|patient_photos)/[A-Za-z0-9_.-]+\.(jpg|jpeg|png)$~i', $path)) {
    require_once __DIR__ . '/session.php';

    if (empty($_SESSION['practitioner_id']) || time() - ($_SESSION['last_activity'] ?? 0) > 1800) {
        redirect_to('/login.php');
    }

    $file = realpath(__DIR__ . $path);
    $folder = realpath(__DIR__ . dirname($path));

    if ($file && $folder && dirname($file) === $folder && is_file($file)) {
        return false;
    }
}

http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'Not found.';