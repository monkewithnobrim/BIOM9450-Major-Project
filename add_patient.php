<?php
//start a session so the success message can survive the redirect
session_start();
date_default_timezone_set('Australia/Sydney');

//load Nathan's database connection for saving patient records
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';

//read the success message once, then remove it from the session
$successMessage = $_SESSION['patient_success'] ?? '';
unset($_SESSION['patient_success']);

//start with empty values when the page first opens
$fields = [
    'first_name' => '',
    'last_name' => '',
    'date_of_birth' => '',
    'gender' => '',
    'room' => '',
    'admission_date' => '',
];

// Track whether the practitioner submitted the form.
$formSubmitted = $_SERVER['REQUEST_METHOD'] === 'POST';

// Read all six fields when the form is submitted.
if ($formSubmitted) {
    foreach (array_keys($fields) as $name) {
        $value = $_POST[$name] ?? '';

        // Accept text and remove spaces from the beginning and end.
        $fields[$name] = is_string($value) ? clean($value) : '';
    }
}

// Collect validation messages. This validates all six fields in PHP and will display any errors
$errors = [];

// Escape submitted text before displaying it inside HTML.
function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}
if ($formSubmitted) {
    // validate patient details using the shared validation functions
    $birthError = validate_date($fields['date_of_birth'], 'Date of birth');
    $admissionError = validate_date($fields['admission_date'], 'Admission date', true);
    $errors = array_values(array_filter([
        validate_name($fields['first_name'], 'First name'),
        validate_name($fields['last_name'], 'Last name'),
        validate_room($fields['room']),
        validate_in_list($fields['gender'], ['F', 'M', 'Other'], 'gender'),
        $birthError,
        $admissionError
    ]));
    // check the relationship between the two dates
    if ($birthError === '' && $admissionError === '' &&
        $fields['admission_date'] < $fields['date_of_birth']) {
        $errors[] = 'Admission date must be on or after the date of birth.';
    }
}

// validate the optional photo using the shared function
$photoExtension = null;
if ($formSubmitted) {
    $photo = $_FILES['photo'] ?? null;
    $photoValidation = validate_photo($photo);
    if ($photoValidation !== '') {
        $errors[] = $photoValidation;
    } elseif ($photo !== null && $photo['error'] === UPLOAD_ERR_OK) {
        // check that the file can be decoded as an image and determine its extension
        $image = @getimagesize($photo['tmp_name']);
        if ($image === false || !in_array($image['mime'], ['image/jpeg', 'image/png'], true)) {
            $errors[] = 'The uploaded file could not be read as an image.';
        } else {
            $photoExtension = $image['mime'] === 'image/jpeg' ? 'jpg' : 'png';
        }
    }
}

// Save the patient only after the form passes every validation check.
if ($formSubmitted && $errors === []) {
    // Use NULL in the database when no photo was selected.
    $photoPath = null;
    $savedPhoto = null;

    try {
        // Save a photo only when the upload validation accepted one.
        if ($photoExtension !== null) {
            // Use the patient_photos folder created beforehand.
            $photoDirectory = __DIR__ . '/patient_photos';

            // Stop the save if the folder is missing.
            if (!is_dir($photoDirectory)) {
                throw new RuntimeException(
                    'The patient_photos folder is missing.'
                );
            }

            // Generate a random filename with the validated extension.
            $filename = bin2hex(random_bytes(16)) . '.' . $photoExtension;

            // Save the relative path in the patient table.
            $photoPath = 'patient_photos/' . $filename;

            // Build the full destination path for moving the file.
            $destination = $photoDirectory . '/' . $filename;

            // Move the uploaded file out of PHP's temporary folder.
            if (!@move_uploaded_file(
                $_FILES['photo']['tmp_name'],
                $destination
            )) {
                throw new RuntimeException(
                    'The patient photo could not be saved.'
                );
            }

            // Remember the saved file in case the database insert fails.
            $savedPhoto = $destination;
        }

        // Insert the values into Nathan's existing patient table.
        $sql = 'INSERT INTO patient
                    (first_name, last_name, date_of_birth, gender,
                     room, photo_path, admission_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)';

        $statement = $conn->prepare($sql);

        // Supply the seven values separately from the SQL statement.
        $statement->bind_param(
            'sssssss',
            $fields['first_name'],
            $fields['last_name'],
            $fields['date_of_birth'],
            $fields['gender'],
            $fields['room'],
            $photoPath,
            $fields['admission_date']
        );

        // Run the insert and get MySQL's automatically generated patient ID.
        $statement->execute();
        $patientID = $conn->insert_id;
        $statement->close();

    } catch (mysqli_sql_exception | RuntimeException $error) {
        // Remove the uploaded photo if the patient could not be inserted.
        if ($savedPhoto !== null && is_file($savedPhoto)) {
            if (!@unlink($savedPhoto)) {
                error_log('Could not remove patient photo: ' . $savedPhoto);
            }
        }

        // Record the technical error for troubleshooting.
        error_log('Patient insert failed: ' . $error->getMessage());

        // Display feedback and retain the entered patient details.
        $errors[] = 'The patient could not be saved. Please try again.';
    }

    if ($errors === []) {
        // Store a confirmation message for the redirected page.
        $_SESSION['patient_success'] =
            'Patient added successfully. Patient ID: ' . $patientID;

        // Open a fresh form after saving to avoid resubmitting on refresh.
        header('Location: add_patient.php', true, 303);
        exit;
    }
}

?>

<!--Task 1: Creating the form for entering a new patients details-->
<!--this sets up the webpage-->
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <!--this is the title shown in the browser tab-->
    <title>Add patient</title>
</head>
<body>
    <h1>Add patient</h1>

    <!--NAVIGATION: access other pages-->
    <p>
        <strong>Add Patient</strong> |
        <a href="patient_search.php">Patient Search</a> |
        <a href="medication_report.php">Medication Report</a>
    </p>

    <!-- Display confirmation after a successful database save -->
    <?php if ($successMessage !== ''): ?>
        <p role="status">
            <?= escapeHtml($successMessage) ?>
        </p>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div role="alert">
            <p>Please correct the following:</p>

            <ul>
                <?php foreach ($errors as $error): ?>
                    <li>
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!--start the patient entry form-->
    <form method="post" enctype="multipart/form-data">

        <!--first name: name must meet requirements of maximum 40 characters -->
        <!--value redisplays the submitted first name after a validation error-->
        <p>
            <label for="first_name">First name</label>
            <input
                type="text"
                id="first_name"
                name="first_name"
                maxlength="40"
                value="<?= escapeHtml($fields['first_name']) ?>"
                required
            >
        </p>

        <!--last name: name must meet requirements of maximum 40 characters-->
        <!--value redisplays the submitted last name after a validation error-->
        <p>
            <label for="last_name">Last name</label>
            <input
                type="text"
                id="last_name"
                name="last_name"
                maxlength="40"
                value="<?= escapeHtml($fields['last_name']) ?>"
                required
            >
        </p>

        <!--date of birth: required date field -->
        <!--value redisplays the submitted date of birth-->
        <p>
            <label for="date_of_birth">Date of birth</label>
            <input
                type="date"
                id="date_of_birth"
                name="date_of_birth"
                value="<?= escapeHtml($fields['date_of_birth']) ?>"
                required
            >
        </p>

        <!-- gender: submit one of the values database table accepts -->
        <!--selected keeps the submitted gender chosen in the dropdown-->
        <p>
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="">Select gender</option>
                <option value="F"
                    <?= $fields['gender'] === 'F' ? 'selected' : '' ?>>
                    Female
                </option>
                <option value="M"
                    <?= $fields['gender'] === 'M' ? 'selected' : '' ?>>
                    Male
                </option>
                <option value="Other"
                    <?= $fields['gender'] === 'Other' ? 'selected' : '' ?>>
                    Other
                </option>
            </select>
        </p>

        <!-- room: required text field, maximum 10 characters.for example, text allows room values such as 12A-->
        <!--value redisplays the submitted room-->
        <p>
            <label for="room">Room</label>
            <input
                type="text"
                id="room"
                name="room"
                maxlength="10"
                value="<?= escapeHtml($fields['room']) ?>"
                required
            >
        </p>

        <!--admission date: required date field-->
        <!--value redisplays the submitted admission date-->
        <p>
            <label for="admission_date">Admission date</label>
            <input
                type="date"
                id="admission_date"
                name="admission_date"
                value="<?= escapeHtml($fields['admission_date']) ?>"
                required
            >
        </p>

        <!--photo: optional file picker for JPEG or PNG images -->
        <!--browsers require the user to select the photo again after submission-->
        <p>
            <label for="photo">Patient photo (optional)</label>
            <input
                type="file"
                id="photo"
                name="photo"
                accept="image/jpeg,image/png"
                aria-describedby="photo_hint"
            >
            <small id="photo_hint">
                <?php if ($errors !== []): ?>
                    If you need to correct an error, select your photo again.
                <?php endif; ?>
            </small>
        </p>

        <!--submit all the fields above-->
        <button type="submit">Add patient</button>
    </form>
</body>
</html>

