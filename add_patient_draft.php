<?php
// Task 1: Insert patient data
require_once __DIR__ .'/db.php';
session_start();

// Read the success flag once, then clear it.
$success = $_SESSION['patient_added'] ?? false;
unset($_SESSION['patient_added']);

// Start with empty values when the form first opens.
$fields = [
    'first_name' => '',
    'last_name' => '',
    'date_of_birth' => '',
    'gender' => '',
    'room' => '',
    'admission_date' => '',
];

// The keys match Nathan's database values.
// The labels are what the practitioner sees on the form.
$genderOptions = [
    'F' => 'Female',
    'M' => 'Male',
    'Other' => 'Other',
];

$errors = [];

// Escape values before displaying them in HTML.
function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
// Check that the date is in YYYY-MM-DD format and exists on the calendar.
function validPatientDate(string $value): bool
{
    if (!preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/', $value, $parts)) {
        return false;
    }

    $year = (int) $parts[1];
    $month = (int) $parts[2];
    $day = (int) $parts[3];

    return $year >= 1000 && checkdate($month, $day, $year);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Each key in $fields matches a name attribute in the HTML form.
    foreach (array_keys($fields) as $name) {
        $value = $_POST[$name] ?? '';
        $fields[$name] = is_string($value) ? trim($value) : '';
    }

    // Match the character limits in Nathan's patient table.
    if ($fields['first_name'] === '' || mb_strlen($fields['first_name'], 'UTF-8') > 40) {
        $errors[] = 'First name must contain between 1 and 40 characters.';
    }

    if ($fields['last_name'] === '' || mb_strlen($fields['last_name'], 'UTF-8') > 40) {
        $errors[] = 'Last name must contain between 1 and 40 characters.';
    }

    if ($fields['room'] === '' || mb_strlen($fields['room'], 'UTF-8') > 10) {
        $errors[] = 'Room must contain between 1 and 10 characters.';
    }

    if (!isset($genderOptions[$fields['gender']])) {
        $errors[] = 'Choose a valid gender from the list.';
    }

    $birthDate = $fields['date_of_birth'];
    $admissionDate = $fields['admission_date'];
    $today = (new DateTimeImmutable('now', new DateTimeZone('Australia/Sydney')))
        ->format('Y-m-d');

    $birthDateIsValid = validPatientDate($birthDate);

    if (!$birthDateIsValid || $birthDate > $today) {
        $errors[] = 'Enter a valid date of birth that is today or earlier.';
    }

    if (!validPatientDate($admissionDate)) {
        $errors[] = 'Enter a valid admission date.';
    } elseif ($birthDateIsValid && $admissionDate < $birthDate) {
        $errors[] = 'Admission date must be on or after the date of birth.';
    }
}

?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add patient</title>
</head>
<body>
    <h1>Add patient</h1>

    <?php if ($errors !== []): ?>
        <div role="alert">
            <p>Please correct the following:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= escapeHtml($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <p>
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" maxlength="40" required>
        </p>

        <p>
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" maxlength="40" required>
        </p>

        <p>
            <label for="date_of_birth">Date of birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth" required>
        </p>

        <p>
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="">Select gender</option>
                <option value="F">Female</option>
                <option value="M">Male</option>
                <option value="Other">Other</option>
            </select>
        </p>

        <p>
            <label for="room">Room</label>
            <input type="text" id="room" name="room" maxlength="10" required>
        </p>

        <p>
            <label for="admission_date">Admission date</label>
            <input type="date" id="admission_date" name="admission_date" required>
        </p>

        <p>
            <label for="photo">Patient photo (optional)</label>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png">
        </p>

        <button type="submit">Add patient</button>
    </form>
</body>
</html>