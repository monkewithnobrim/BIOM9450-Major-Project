<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POST required.');
}

$adminId = filter_var($_POST['admin_id'] ?? null, FILTER_VALIDATE_INT);
$patientId = filter_var($_POST['patient_id'] ?? null, FILTER_VALIDATE_INT);
$date = is_string($_POST['date'] ?? null) ? $_POST['date'] : '';
$round = is_string($_POST['round'] ?? null) ? $_POST['round'] : '';
$viewRound = is_string($_POST['view_round'] ?? null) ? $_POST['view_round'] : $round;
$status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
$notes = is_string($_POST['notes'] ?? null) ? trim($_POST['notes']) : '';
$rounds = ['morning', 'afternoon', 'evening'];
$statuses = ['given', 'refused', 'fasting', 'no stock', 'ceased'];
$url = 'dispensing.php?' . http_build_query([
    'date' => $date,
    'round' => $viewRound,
    'patient_id' => $patientId
]);

unset($_SESSION['round_warning']);

if (
    !$adminId || $adminId < 1 ||
    !$patientId || $patientId < 1 ||
    validate_date($date, 'Date', true) ||
    validate_in_list($round, $rounds, 'round') ||
    !in_array($viewRound, ['all', $round], true) ||
    validate_in_list($status, $statuses, 'status') ||
    !is_string($_POST['notes'] ?? '') || mb_strlen($notes, 'UTF-8') > 255
) {
    $_SESSION['round_message'] = 'Invalid outcome or schedule. Nothing was saved.';
    redirect_to($url);
}

$practitioner = (int) $_SESSION['practitioner_id'];
$time = date('Y-m-d H:i:s');
$saved = false;

try {
    $stmt = $conn->prepare(
        "UPDATE medication_administration a
         JOIN medication_order o ON a.order_id = o.order_id
         SET a.status = ?, a.practitioner_id = ?,
             a.administered_at = ?, a.notes = NULLIF(?, '')
         WHERE a.admin_id = ? AND a.scheduled_date = ?
               AND o.patient_id = ? AND o.round = ?
               AND a.status = 'pending'"
    );
    $stmt->bind_param('sissisis', $status, $practitioner, $time, $notes, $adminId, $date, $patientId, $round);
    $stmt->execute();
    $saved = $stmt->affected_rows === 1;
    $stmt->close();

    $_SESSION['round_message'] = $saved
        ? 'Medication outcome saved.'
        : 'Record already completed or no longer matches this round. No change made.';
} catch (mysqli_sql_exception $e) {
    error_log('Dispensing update failed: ' . $e->getMessage());
    $_SESSION['round_message'] = 'Unable to save outcome. Check the record before retrying.';
}

if ($saved && $status === 'refused') {
    $director = getenv('CARECODE_DIRECTOR_EMAIL');
    $sender = getenv('CARECODE_SENDER_EMAIL') ?: 'carecode@carehome.example';

    if (!filter_var($director, FILTER_VALIDATE_EMAIL) || !filter_var($sender, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['round_warning'] = 'Refusal recorded. Email is not configured. Please notify the facility director manually.';
    } else {
        $subject = 'Medication refusal notification';
        $body = 'Patient ID: ' . $patientId . "\n" .
            'Practitioner: ' . $_SESSION['full_name'] . "\n" .
            'Scheduled date: ' . $date . "\n" .
            'Round: ' . ucfirst($round);
        $sent = false;

        try {
            $sent = @mail($director, $subject, $body, ['From' => $sender]);
        } catch (Throwable $e) {
            error_log('Refusal notification failed: ' . $e->getMessage());
        }

        if ($sent) {
            $_SESSION['round_message'] .= ' Refusal notification accepted by the mail service.';
        } else {
            $_SESSION['round_warning'] = 'Refusal recorded, but the email could not be sent. Please notify the facility director manually.';
        }
    }
}

redirect_to($url);