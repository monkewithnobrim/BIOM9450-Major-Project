<?php
//database connection: load existing MySQLi connection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';

//patient list setup: start with no records and no error message
$patients = [];
$errorMessage = '';

//search input: read the submitted search text and remove surrounding spaces
$searchValue = $_GET['search'] ?? '';
$search = is_string($searchValue) ? clean($searchValue) : '';

//search validation: reject incorrectly structured or overly long search values
if (!is_string($searchValue) || mb_strlen($search, 'UTF-8') > 100) {
    $errorMessage = 'Enter search text containing no more than 100 characters.';
}

//html output: escape database values before displaying them on the page
function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

//patient retrieval: run the query when the search input is valid
if ($errorMessage === '') {
    try {
        if ($search === '') {
            //browse query: retrieve all patients when the search is empty
            //patient photo data: retrieve each patient's stored photo path with their details
            $sql = 'SELECT patient_id, first_name, last_name,
                    date_of_birth, gender, room, admission_date, photo_path
                    FROM patient
                    ORDER BY last_name, first_name, patient_id';

            $result = $conn->query($sql);

        } else {
            //search query: match names or rooms containing the search text
            //patient IDs must match the complete search value
            //patient photo data: retrieve each patient's stored photo path with their details
            $sql = 'SELECT patient_id, first_name, last_name,
                    date_of_birth, gender, room, admission_date, photo_path
                    FROM patient
                    WHERE LOCATE(?, first_name) > 0
                       OR LOCATE(?, last_name) > 0
                       OR LOCATE(?, CONCAT(first_name, " ", last_name)) > 0
                       OR LOCATE(?, room) > 0
                       OR CAST(patient_id AS CHAR) = ?
                    ORDER BY last_name, first_name, patient_id';

            //prepared statement: supply search values separately from the SQL
            $statement = $conn->prepare($sql);
            $statement->bind_param(
                'sssss',
                $search,
                $search,
                $search,
                $search,
                $search
            );

            $statement->execute();
            $result = $statement->get_result();
        }

        //patient list: copy each matching database row into the array
        while ($patient = $result->fetch_assoc()) {
            $patients[] = $patient;
        }

        //query cleanup: release the result after reading the records
        $result->free();

        if ($search !== '') {
            $statement->close();
        }

    } catch (mysqli_sql_exception $error) {
        //error handling: log the technical details and prepare user feedback
        error_log('Patient search failed: ' . $error->getMessage());
        $errorMessage = 'The patient list could not be loaded. Please try again.';
    }
}

?>

<!-- Task 2: This is the webpage for practitioners to browse, search and edit patient records-->
<!doctype html>
<html lang="en">
<head>
    <!--support text characters used in patient names-->
    <meta charset="utf-8">

    <!--make the page fit different screen widths-->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!--set the browser tab title-->
    <title>Patient records</title>
</head>
<body>
    <!--page heading-->
    <h1>Patient records</h1>

    <?php if ($errorMessage !== ''): ?>
        <!--query feedback: display an error if the records could not be read-->
        <p role="alert">
            <?= htmlspecialchars(
                $errorMessage,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) ?>
        </p>
    <?php else: ?>
        <!--temporary check: show how many patient records were retrieved-->
        <p>Patient records loaded: <?= count($patients) ?></p>
    <?php endif; ?>

    <!--NAVIGATION: access other pages-->
    <p>
        <a href="add_patient.php">Add Patient</a> |
        <strong>Patient Search</strong> |
        <a href="medication_report.php">Medication Report</a>
    </p>

    <!--SEARCH PATIENT section: collect a name, patient ID or room to search for-->
    <section aria-labelledby="patient-search-heading">
        <h2 id="patient-search-heading">Search patients</h2>

        <!--search form: send the search text to this page using GET-->
        <form action="patient_search.php" method="get">
            <p>
                <label for="search">Patient name, ID or room</label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    maxlength="100"
                    placeholder="For example, Margaret, 1 or A01"
                    value="<?= escapeHtml($search) ?>"
                >
            </p>

            <!--search controls: submit the search or clear it and reload all patients-->
            <button type="submit">Search</button>
            <button
                type="button"
                onclick="window.location.href='patient_search.php';"
            >
                Clear search
            </button>

        </form>
    </section>

    <!--LIST PATIENT section: display the records retrieved from Nathan's database-->
    <section aria-labelledby="patient-list-heading">
        <h2 id="patient-list-heading">Patient list</h2>

        <?php if ($errorMessage === ''): ?>
            <?php if ($patients === []): ?>
                <!--empty results: distinguish an unsuccessful search from an empty database-->
                <?php if ($search !== ''): ?>
                    <p>No patients match your search</p>
                <?php else: ?>
                    <p>No patients have been added yet</p>
                <?php endif; ?>

            <?php else: ?>
                <!--patient table: display one row for each patient-->
                <table>
                    <caption>Patients listed alphabetically by last name</caption>

                    <!--table headings: identify the information in each column-->
                    <thead>
                        <tr>
                            <th scope="col">Patient ID</th>
                            <th scope="col">First name</th>
                            <th scope="col">Last name</th>
                            <th scope="col">Date of birth</th>
                            <th scope="col">Gender</th>
                            <th scope="col">Room</th>
                            <th scope="col">Admission date</th>
                            <!--patient photo column: identify the image displayed for each patient-->
                            <th scope="col">Photo</th>
                            <!--provide access to editing a patient-->
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>

                    <!--table body: repeat the row below for every patient-->
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?= escapeHtml((string) $patient['patient_id']) ?></td>
                                <td><?= escapeHtml($patient['first_name']) ?></td>
                                <td><?= escapeHtml($patient['last_name']) ?></td>
                                <td><?= escapeHtml($patient['date_of_birth']) ?></td>
                                <td><?= escapeHtml($patient['gender']) ?></td>
                                <td><?= escapeHtml($patient['room']) ?></td>
                                <td><?= escapeHtml($patient['admission_date']) ?></td>
                                <!--edit action: send the selected patient's ID to the edit page-->
                                <!--patient photo: check that the stored path points to an existing local image-->
                                <td>
                                    <?php
                                    //photo path: read the database value and locate the actual file
                                    $photoPath = $patient['photo_path'] ?? '';
                                    $photoFile = $photoPath !== ''
                                        ? realpath(__DIR__ . '/' . $photoPath)
                                        : false;

                                    //allowed folders: support preloaded photos and photos uploaded through the form
                                    $photoFolders = [
                                        realpath(__DIR__ . '/photos'),
                                        realpath(__DIR__ . '/patient_photos'),
                                    ];

                                    //file validation: accept existing image files directly inside an allowed folder
                                    $photoAvailable = $photoFile !== false
                                        && is_file($photoFile)
                                        && in_array(dirname($photoFile), $photoFolders, true)
                                        && in_array(
                                            strtolower(pathinfo($photoFile, PATHINFO_EXTENSION)),
                                            ['jpg', 'jpeg', 'png'],
                                            true
                                        );
                                    ?>

                                    <?php if ($photoAvailable): ?>
                                        <img
                                            src="<?= escapeHtml($photoPath) ?>"
                                            alt="<?= escapeHtml(
                                                'Photo of ' . $patient['first_name'] . ' ' . $patient['last_name']
                                            ) ?>"
                                            width="80"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <!--missing photo feedback: cover empty paths and unavailable image files-->
                                        <span>No photo available</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="patient_edit.php?patient_id=<?= (int) $patient['patient_id'] ?>">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </section>

</body>
</html>