<?php
// session setup: start the session for this page
session_start();

// timezone setup: use Sydney local time
date_default_timezone_set('Australia/Sydney');

// database connection: load the existing database connection
require_once __DIR__ . '/db.php';

// report setup: initialise variables
$patients = [];
$reportRows = [];
$groupedRecords = [];
$errorMessage = '';
$reportError = '';
$emailMessages = [];

// filter setup: capture the selected patient and reporting period
$selectedPatient = $_GET['patient_id'] ?? '';
$selectedPeriod = $_GET['report_period'] ?? '';
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
    if (!in_array($selectedPeriod, ['previous_week', 'upcoming_week'], true)) {
        $reportError = 'Please select a valid reporting period.';
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
                    pr.full_name AS practitioner_name,
                    pr.email AS practitioner_email
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

// refusal notification: notify the facility director about refused doses
if ($selectedPeriod === 'previous_week' && $reportError === '' && $errorMessage === '') {
    try {
        // director retrieval: retrieve the facility director email address
        $directorResult = $conn->query(
            "SELECT email FROM practitioner WHERE role = 'administrator' LIMIT 1"
        );

        $director = $directorResult->fetch_assoc();
        $directorResult->free();

        if ($director && filter_var($director['email'], FILTER_VALIDATE_EMAIL)) {
            // notification tracking: initialise the session notification list
            if (!isset($_SESSION['medication_refusal_notifications'])) {
                $_SESSION['medication_refusal_notifications'] = [];
            }

            // refusal identification: check the report for refused doses
            foreach ($reportRows as $record) {
                if ($record['status'] === 'refused') {
                    $notificationId = (string) $record['admin_id'];

                    // notification check: skip records already successfully emailed
                    if (!in_array($notificationId, $_SESSION['medication_refusal_notifications'], true)) {
                        // email content: prepare the refused medication information
                        $subject = 'Medication Refusal Notification';
                        $body = "A medication dose has been refused\n\n";
                        $body .= "Patient ID: " . $record['patient_id'] . "\n";
                        $body .= "Patient Name: " . $record['first_name'] . ' ' . $record['last_name'] . "\n";
                        $body .= "Medication: " . $record['medication_name'] . "\n";
                        $body .= "Dose: " . $record['dose'] . "\n";
                        $body .= "Scheduled Date: " . $record['scheduled_date'] . "\n";
                        $body .= "Practitioner: " . ($record['practitioner_name'] ?? 'Not recorded') . "\n";
                        $body .= "Notes: " . ($record['notes'] ?? 'No additional notes') . "\n";

                        // email headers: specify the plain text email format
                        $headers = "Content-Type: text/plain; charset=UTF-8";

                        // email delivery: send the notification to the facility director
                        $emailSent = mail($director['email'], $subject, $body, $headers);

                        // notification tracking: record the notification after successful submission
                        if ($emailSent) {
                            $_SESSION['medication_refusal_notifications'][] = $notificationId;
                            $emailMessages[] = 'Refusal notification submitted for patient ID ' . $record['patient_id'];
                        } else {
                            $emailMessages[] = 'Email delivery failed for patient ID ' . $record['patient_id'] . '. Check the PHP email configuration.';
                        }
                    }
                }
            }
        } else {
            $emailMessages[] = 'No valid facility director email address was found.';
        }

    } catch (mysqli_sql_exception $error) {
        // notification error handling: log errors during notification processing
        error_log('Medication refusal notification failed: ' . $error->getMessage());
        $emailMessages[] = 'The refusal notification could not be processed.';
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
</head>
<body>

    <!-- report heading: identify the purpose of the webpage -->
    <h1>Medication Report</h1>

    <!-- report filter section: allow users to select patients and reporting periods -->
    <h2>Select Report Criteria</h2>

    <?php if ($errorMessage !== ''): ?>
        <p><?php echo escapeHtml($errorMessage); ?></p>
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
        <p><?php echo escapeHtml($reportError); ?></p>
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

        <!-- refusal notification results: display the outcome of notification attempts -->
        <?php foreach ($emailMessages as $message): ?>
            <p><?php echo escapeHtml($message); ?></p>
        <?php endforeach; ?>

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

                    <table border="1" cellpadding="6">
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
                                    <td><?php echo escapeHtml(ucwords($record['status'])); ?></td>
                                    <td><?php echo $record['administered_at'] !== null ? escapeHtml($record['administered_at']) : 'Not recorded'; ?></td>
                                    <td><?php echo $record['notes'] !== null ? escapeHtml($record['notes']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

                    <table border="1" cellpadding="6">
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
                                    <td><?php echo escapeHtml(ucwords($record['status'])); ?></td>
                                    <td><?php echo $record['notes'] !== null ? escapeHtml($record['notes']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <br>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

</body>
</html>
