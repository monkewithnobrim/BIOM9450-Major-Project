<?php
require_once __DIR__ . '/session.php';

if (empty($_SESSION['practitioner_id']) || time() - ($_SESSION['last_activity'] ?? 0) > 1800) {
    $_SESSION = [];
    session_regenerate_id(true);
    redirect_to('login.php');
}

$_SESSION['last_activity'] = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}