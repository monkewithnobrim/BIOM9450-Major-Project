<?php
require_once __DIR__ . '/auth.php';
// session setup: start the session for this page


// timezone setup: use Sydney local time
date_default_timezone_set('Australia/Sydney');

//load database connection for saving patient records
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';

// report setup: initialise variables
$patients = [];
$reportRows = [];
$groupedRecords = [];
$errorMessage = '';
$reportError = '';

// filter setup: capture the selected patient and reporting period
$patientInput = $_GET['patient_id'] ?? '';
$periodInput = $_GET['report_period'] ?? '';
$selectedPatient = is_string($patientInput) ? clean($patientInput) : '';
$selectedPeriod = is_string($periodInput) ? clean($periodInput) : '';
if (!is_string($patientInput) || !is_string($periodInput)) {
    $reportError = 'Please select valid report criteria.';
}
$startDate = '';
$endDate = '';

// html output: safely escape values before displaying them
function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// date display: convert database dates into readable dates
function formatReportDate(string $date): string
{
    return date('d/m/Y', strtotime($date));
}

// patient retrieval: retrieve all patients for the selection dropdown
try {
    $sql = 'SELECT patient_id, first_name, last_name
            FROM patient
            ORDER BY last_name, first_name';

    $result = $conn->query($sql);

    while ($patient = $result->fetch_assoc()) {
        $patients[] = $patient;
    }

    $result->free();

} catch (mysqli_sql_exception $error) {
    // error handling: log the technical error and prepare user feedback
    error_log('Patient retrieval failed: ' . $error->getMessage());
    $errorMessage = 'The patient list could not be loaded. Please try again.';
}

// patient validation: check that the selected patient exists
if ($selectedPatient !== '') {
    if (!ctype_digit((string) $selectedPatient)) {
        $reportError = 'Please select a valid patient.';
    } else {
        $patientExists = false;

        foreach ($patients as $patient) {
            if ((string) $patient['patient_id'] === (string) $selectedPatient) {
                $patientExists = true;
                break;
            }
        }

        if (!$patientExists) {
            $reportError = 'The selected patient could not be found.';
        }
    }
}

// reporting period calculation: calculate the previous and upcoming weeks
if ($selectedPeriod !== '') {
    if (($periodError = validate_in_list($selectedPeriod, ['previous_week', 'upcoming_week'], 'reporting period')) !== '') {
        $reportError = $periodError;
    } else {
        // upcoming week: the demonstration data is fixed to start on Monday 30 November 2026
        $upcomingStart = new DateTime('2026-11-30');

        // previous week: calculate the Monday and Sunday before the upcoming week
        $previousStart = clone $upcomingStart;
        $previousStart->modify('-7 days');

        $previousEnd = clone $upcomingStart;
        $previousEnd->modify('-1 day');

        // upcoming week: calculate the Sunday after the upcoming Monday
        $upcomingEnd = clone $upcomingStart;
        $upcomingEnd->modify('+6 days');

        // report dates: assign the dates for the selected period
        if ($selectedPeriod === 'previous_week') {
            $startDate = $previousStart->format('Y-m-d');
            $endDate = $previousEnd->format('Y-m-d');
        } else {
            $startDate = $upcomingStart->format('Y-m-d');
            $endDate = $upcomingEnd->format('Y-m-d');
        }
    }
}

// medication report retrieval: retrieve records for the selected reporting period
if ($selectedPeriod !== '' && $reportError === '' && $errorMessage === '') {
    try {
        // medication query: join the five database tables to retrieve report details
        $sql = 'SELECT
                    a.admin_id,
                    a.scheduled_date,
                    a.status,
                    a.practitioner_id,
                    a.administered_at,
                    a.notes,
                    p.patient_id,
                    p.first_name,
                    p.last_name,
                    p.room,
                    m.name AS medication_name,
                    m.strength,
                    m.form,
                    m.route,
                    o.dose,
                    o.round,
                    pr.full_name AS practitioner_name
                FROM medication_administration a
                INNER JOIN medication_order o ON a.order_id = o.order_id
                INNER JOIN patient p ON o.patient_id = p.patient_id
                INNER JOIN medication m ON o.medication_id = m.medication_id
                LEFT JOIN practitioner pr ON a.practitioner_id = pr.practitioner_id
                WHERE a.scheduled_date BETWEEN ? AND ?';

        // patient filter: restrict the report when one patient is selected
        if ($selectedPatient !== '') {
            $sql .= ' AND p.patient_id = ?';
        }

        // report sorting: organise records by practitioner, patient and date
        $sql .= ' ORDER BY
                    COALESCE(pr.full_name, "Practitioner not assigned"),
                    p.last_name,
                    p.first_name,
                    a.scheduled_date,
                    FIELD(o.round, "morning", "afternoon", "evening")';

        // prepared statement: prepare and execute the database query
        $stmt = $conn->prepare($sql);

        if ($selectedPatient !== '') {
            $patientId = (int) $selectedPatient;
            $stmt->bind_param('ssi', $startDate, $endDate, $patientId);
        } else {
            $stmt->bind_param('ss', $startDate, $endDate);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        // report records: store each matching medication record
        while ($record = $result->fetch_assoc()) {
            $reportRows[] = $record;
        }

        $result->free();
        $stmt->close();

        // practitioner grouping: organise records under each practitioner
        foreach ($reportRows as $record) {
            $practitionerGroup = $record['practitioner_name'] ?? 'Practitioner not assigned';
            $groupedRecords[$practitionerGroup][] = $record;
        }

    } catch (mysqli_sql_exception $error) {
        // error handling: log the technical error and prepare user feedback
        error_log('Medication report retrieval failed: ' . $error->getMessage());
        $reportError = 'The medication report could not be generated. Please try again.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- page setup: define the webpage title and character encoding -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Report</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="report-page">
    <?php require __DIR__ . '/header.php'; ?>
    <!-- CARECODE PAGE STYLING -->

    <!-- report heading: identify the purpose of the webpage -->
    <h1>Medication Report</h1>

    <!-- report filter section: allow users to select patients and reporting periods -->
    <h2>Select Report Criteria</h2>

    <?php if ($errorMessage !== ''): ?>
        <p role="alert"><?php echo escapeHtml($errorMessage); ?></p>
    <?php else: ?>
        <form method="GET" action="medication_report.php">
            <!-- patient selection: allow users to select all patients or one patient -->
            <label for="patient_id">Patient:</label>
            <select name="patient_id" id="patient_id">
                <option value="">All Patients</option>
                <?php foreach ($patients as $patient): ?>
                    <option
                        value="<?php echo escapeHtml((string) $patient['patient_id']); ?>"
                        <?php echo (string) $selectedPatient === (string) $patient['patient_id'] ? 'selected' : ''; ?>
                    >
                        <?php echo escapeHtml($patient['first_name'] . ' ' . $patient['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <br><br>

            <!-- reporting period selection: allow users to select the previous or upcoming week -->
            <label for="report_period">Reporting Period:</label>
            <select name="report_period" id="report_period" required>
                <option value="">Select Reporting Period</option>
                <option value="previous_week" <?php echo $selectedPeriod === 'previous_week' ? 'selected' : ''; ?>>
                    Previous Week
                </option>
                <option value="upcoming_week" <?php echo $selectedPeriod === 'upcoming_week' ? 'selected' : ''; ?>>
                    Upcoming Week
                </option>
            </select>

            <br><br>
            <button type="submit">Generate Report</button>
        </form>
    <?php endif; ?>

    <!-- report error section: display errors encountered while generating the report -->
    <?php if ($reportError !== ''): ?>
        <p role="alert"><?php echo escapeHtml($reportError); ?></p>
    <?php endif; ?>

    <!-- report results section: display the selected criteria and medication records -->
    <?php if ($startDate !== '' && $endDate !== '' && $reportError === '' && $errorMessage === ''): ?>
        <hr>

        <!-- report summary: display the selected patient and reporting period -->
        <h2>
            <?php echo $selectedPeriod === 'previous_week' ? 'Previous Week Medication Report' : 'Upcoming Week Medication Report'; ?>
        </h2>

        <p>
            <strong>Patient:</strong>
            <?php
            if ($selectedPatient === '') {
                echo 'All Patients';
            } else {
                foreach ($patients as $patient) {
                    if ((string) $patient['patient_id'] === (string) $selectedPatient) {
                        echo escapeHtml($patient['first_name'] . ' ' . $patient['last_name']);
                        break;
                    }
                }
            }
            ?>
        </p>

        <p>
            <strong>Reporting Period:</strong>
            <?php echo escapeHtml(formatReportDate($startDate)); ?>
            to
            <?php echo escapeHtml(formatReportDate($endDate)); ?>
        </p>

        <p><strong>Total Medication Records:</strong> <?php echo count($reportRows); ?></p>

        <!-- previous week report: display actual medication administration outcomes -->
        <?php if ($selectedPeriod === 'previous_week'): ?>
            <h2>Medication Administration History</h2>
            <p>This report displays medication administration records and their recorded outcomes for the selected period</p>

            <?php if (empty($groupedRecords)): ?>
                <p>No medication administration records were found for this period</p>
            <?php else: ?>
                <!-- practitioner grouping: display medication records under each practitioner -->
                <?php foreach ($groupedRecords as $practitionerName => $records): ?>
                    <h3>Practitioner: <?php echo escapeHtml($practitionerName); ?></h3>

                    <div class="table-scroll">
<table>
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Patient Name</th>
                                <th>Room</th>
                                <th>Medication</th>
                                <th>Strength</th>
                                <th>Dose</th>
                                <th>Round</th>
                                <th>Route</th>
                                <th>Scheduled Date</th>
                                <th>Outcome</th>
                                <th>Administered At</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo escapeHtml((string) $record['patient_id']); ?></td>
                                    <td><?php echo escapeHtml($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                    <td><?php echo escapeHtml($record['room']); ?></td>
                                    <td><?php echo escapeHtml($record['medication_name']); ?></td>
                                    <td><?php echo escapeHtml($record['strength']); ?></td>
                                    <td><?php echo escapeHtml($record['dose']); ?></td>
                                    <td><?php echo escapeHtml(ucfirst($record['round'])); ?></td>
                                    <td><?php echo escapeHtml(ucfirst($record['route'])); ?></td>
                                    <td><?php echo escapeHtml(formatReportDate($record['scheduled_date'])); ?></td>
                                    <td><span class="status <?= escapeHtml(str_replace(' ', '-', $record['status'])) ?>"><?php echo escapeHtml(ucwords($record['status'])); ?></span></td>
                                    <td><?php echo $record['administered_at'] !== null ? escapeHtml($record['administered_at']) : 'Not recorded'; ?></td>
                                    <td><?php echo $record['notes'] !== null ? escapeHtml($record['notes']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
</div>
                    <br>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>

        <!-- upcoming week report: display future medication schedules -->
        <?php if ($selectedPeriod === 'upcoming_week'): ?>
            <h2>Upcoming Medication Schedule</h2>
            <p>This report displays scheduled medication doses for the upcoming week</p>

            <?php if (empty($groupedRecords)): ?>
                <p>No upcoming medication schedules were found for this period</p>
            <?php else: ?>
                <!-- practitioner grouping: organise future schedules by assigned practitioner -->
                <?php foreach ($groupedRecords as $practitionerName => $records): ?>
                    <h3>Practitioner: <?php echo escapeHtml($practitionerName); ?></h3>

                    <div class="table-scroll">
<table>
                        <thead>
                            <tr>
                                <th>Patient ID</th>
                                <th>Patient Name</th>
                                <th>Room</th>
                                <th>Medication</th>
                                <th>Strength</th>
                                <th>Dose</th>
                                <th>Round</th>
                                <th>Route</th>
                                <th>Scheduled Date</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo escapeHtml((string) $record['patient_id']); ?></td>
                                    <td><?php echo escapeHtml($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                    <td><?php echo escapeHtml($record['room']); ?></td>
                                    <td><?php echo escapeHtml($record['medication_name']); ?></td>
                                    <td><?php echo escapeHtml($record['strength']); ?></td>
                                    <td><?php echo escapeHtml($record['dose']); ?></td>
                                    <td><?php echo escapeHtml(ucfirst($record['round'])); ?></td>
                                    <td><?php echo escapeHtml(ucfirst($record['route'])); ?></td>
                                    <td><?php echo escapeHtml(formatReportDate($record['scheduled_date'])); ?></td>
                                    <td><span class="status <?= escapeHtml(str_replace(' ', '-', $record['status'])) ?>"><?php echo escapeHtml(ucwords($record['status'])); ?></span></td>
                                    <td><?php echo $record['notes'] !== null ? escapeHtml($record['notes']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
</div>
                    <br>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>


    <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
