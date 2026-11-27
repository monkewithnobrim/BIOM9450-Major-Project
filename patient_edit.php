<?php
require_once __DIR__ . '/auth.php';
//session setup: carry the success message across the redirect

date_default_timezone_set('Australia/Sydney');

//database connection: load existing MySQLi connection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';

//success feedback: read the message once and remove it from the session
$successMessage = $_SESSION['patient_edit_success'] ?? '';
unset($_SESSION['patient_edit_success']);

//patient selection: read the patient ID from the URL
$patientID = filter_input(
    INPUT_GET,
    'patient_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 2147483647]]
);

//selection validation: prepare feedback when the ID is missing or invalid
$errorMessage = '';

if ($patientID === false || $patientID === null) {
    $errorMessage = 'Choose a valid patient from the patient list.';
}

//patient lookup: start without a selected patient record
$patient = null;

if ($errorMessage === '') {
    try {
        //patient query: retrieve the record matching the selected ID
        $sql = 'SELECT patient_id, first_name, last_name,
                       date_of_birth, gender, room, admission_date
                FROM patient
                WHERE patient_id = ?';

        //prepared statement: supply the patient ID separately from the SQL
        $statement = $conn->prepare($sql);
        $statement->bind_param('i', $patientID);
        $statement->execute();

        //patient record: read the matching row as an associative array
        $result = $statement->get_result();
        $patient = $result->fetch_assoc();

        //query cleanup: release the result and close the statement
        $result->free();
        $statement->close();

        //missing patient feedback: handle an ID that has no matching record
        if ($patient === null) {
            $errorMessage = 'The selected patient could not be found.';
        }

    } catch (mysqli_sql_exception $error) {
        //error handling: log technical details and prepare user feedback
        error_log('Patient lookup failed: ' . $error->getMessage());
        $errorMessage = 'The patient details could not be loaded. Please try again.';
    }
}

//html output: escape text before displaying it on the page
function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

//edit validation setup: start with an empty list of field errors
$errors = [];

//edit submission: process changes only when the selected patient was loaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    $errorMessage === '' &&
    $patient !== null) {

    //editable fields: identify the six values the practitioner can change
    $editableFields = [
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'room',
        'admission_date',
    ];

    //submitted values: replace displayed values with the practitioner's edits
    foreach ($editableFields as $name) {
        $value = $_POST[$name] ?? '';
        $patient[$name] = is_string($value) ? clean($value) : '';
    }

    // validate patient details using the shared validation functions
    $birthError = validate_date($patient['date_of_birth'], 'Date of birth');
    $admissionError = validate_date($patient['admission_date'], 'Admission date', true);
    $errors = array_values(array_filter([
        validate_name($patient['first_name'], 'First name'),
        validate_name($patient['last_name'], 'Last name'),
        validate_room($patient['room']),
        validate_in_list($patient['gender'], ['F', 'M', 'Other'], 'gender'),
        $birthError,
        $admissionError
    ]));
    // check the relationship between the two dates
    if ($birthError === '' && $admissionError === '' &&
        $patient['admission_date'] < $patient['date_of_birth']) {
        $errors[] = 'Admission date must be on or after the date of birth.';
    }
}

//patient update: save only when the selected record and edited values are valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    $errorMessage === '' &&
    $patient !== null &&
    $errors === []) {

    try {
        //update query: change the six editable fields for the selected patient
        $sql = 'UPDATE patient
                SET first_name = ?,
                    last_name = ?,
                    date_of_birth = ?,
                    gender = ?,
                    room = ?,
                    admission_date = ?
                WHERE patient_id = ?';

        //prepared statement: bind six text values and the integer patient ID
        $statement = $conn->prepare($sql);
        $statement->bind_param(
            'ssssssi',
            $patient['first_name'],
            $patient['last_name'],
            $patient['date_of_birth'],
            $patient['gender'],
            $patient['room'],
            $patient['admission_date'],
            $patientID
        );

        //save operation: run the update and release the statement
        $statement->execute();
        $statement->close();

        //success feedback: store confirmation for the redirected page
        $_SESSION['patient_edit_success'] =
            'Patient details saved successfully. Patient ID: ' . $patientID;

        //redirect: reload the saved record without repeating the POST submission
        header(
            'Location: patient_edit.php?patient_id=' . $patientID,
            true,
            303
        );
        exit;

    } catch (mysqli_sql_exception $error) {
        //error handling: log technical details and retain the edited form values
        error_log('Patient update failed: ' . $error->getMessage());
        $errors[] = 'The patient changes could not be saved. Please try again.';
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <!--page setup: support text characters and different screen widths-->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!--browser title: identify the patient editing page-->
    <title>Edit patient</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="patient-form-page">
    <?php require __DIR__ . '/header.php'; ?>
    <!-- CARECODE PAGE STYLING -->
    <!--page heading: identify the current task-->
    <h1>Edit patient</h1>

    <!--success feedback: confirm that the patient update completed-->
    <?php if ($successMessage !== '' && $errorMessage === ''): ?>
        <p role="status">
            <?= escapeHtml($successMessage) ?>
        </p>
    <?php endif; ?>

    <!--navigation: return to the patient list-->
    <p>
        <a href="patient_search.php">Back to patient records</a>
    </p>

    <?php if ($errorMessage !== ''): ?>
        <!--selection feedback: explain when the patient ID is invalid-->
        <p role="alert"><?= escapeHtml($errorMessage) ?></p>
    <?php else: ?>

        <!--patient edit section: show the selected patient's existing details-->
        <p>Patient ID: <?= escapeHtml((string) $patient['patient_id']) ?></p>
        <?php if ($errors !== []): ?>
            <!--edit feedback VALIDATION: display each field validation error above the form-->
            <div role="alert">
                <p>Please correct the following:</p>

                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escapeHtml($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!--patient edit form: submit changes to this page while retaining the patient ID-->
        <form class="patient-form"
            action="patient_edit.php?patient_id=<?= (int) $patient['patient_id'] ?>"
            method="post"
        >
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <!--first name: display the current value and limit entry to 40 characters-->
            <p>
                <label for="first_name">First name</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    maxlength="40"
                    value="<?= escapeHtml($patient['first_name']) ?>"
                    required
                >
            </p>

            <!--last name: display the current value and limit entry to 40 characters-->
            <p>
                <label for="last_name">Last name</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    maxlength="40"
                    value="<?= escapeHtml($patient['last_name']) ?>"
                    required
                >
            </p>

            <!--date of birth: display the patient's current date of birth-->
            <p>
                <label for="date_of_birth">Date of birth</label>
                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="<?= escapeHtml($patient['date_of_birth']) ?>"
                    required
                >
            </p>

            <!--gender: select the value currently stored in Nathan's database-->
            <p>
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">Select gender</option>

                    <option value="F"
                        <?= $patient['gender'] === 'F' ? 'selected' : '' ?>>
                        Female
                    </option>

                    <option value="M"
                        <?= $patient['gender'] === 'M' ? 'selected' : '' ?>>
                        Male
                    </option>

                    <option value="Other"
                        <?= $patient['gender'] === 'Other' ? 'selected' : '' ?>>
                        Other
                    </option>
                </select>
            </p>

            <!--room: display the current room and allow up to 10 characters-->
            <p>
                <label for="room">Room</label>
                <input
                    type="text"
                    id="room"
                    name="room"
                    maxlength="10"
                    value="<?= escapeHtml($patient['room']) ?>"
                    required
                >
            </p>

            <!--admission date: display the patient's current admission date-->
            <p>
                <label for="admission_date">Admission date</label>
                <input
                    type="date"
                    id="admission_date"
                    name="admission_date"
                    value="<?= escapeHtml($patient['admission_date']) ?>"
                    required
                >
            </p>

            <!--edit controls: submit changes or return to the patient list-->
            <button type="submit">Save changes</button>
            <!--cancel button: return to the patient list without submitting changes-->
            <button class="secondary"
                type="button"
                onclick="window.location.href='patient_search.php';"
            >
                Cancel
            </button>
        </form>
    <?php endif; ?>

    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>