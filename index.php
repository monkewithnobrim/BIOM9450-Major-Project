<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$patientsCount = $conn->query('SELECT COUNT(*) AS total FROM patient')->fetch_assoc()['total'];
$counts = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'pending'), 0) AS pending
    FROM medication_administration
")->fetch_assoc();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Overview | CareCode</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php require __DIR__ . '/header.php'; ?>

    <section class="hero-panel">
        <p class="eyebrow">PRACTITIONER WORKSPACE</p>
        <h1>Welcome, <?= h($_SESSION['full_name']) ?></h1>
        <p class="muted">
            Use this dashboard to access medication rounds, patient records
            and medication administration reports.
        </p>
    </section>

    <div class="cards">
        <div class="metric-card">
            <p class="metric-label">Registered residents</p>
            <div class="metric"><?= h($patientsCount) ?></div>
        </div>

        <div class="metric-card">
            <p class="metric-label">Scheduled medication doses</p>
            <div class="metric"><?= h($counts['total']) ?></div>
        </div>

        <div class="metric-card">
            <p class="metric-label">Pending doses</p>
            <div class="metric"><?= h($counts['pending']) ?></div>
        </div>
    </div>

    <h2>Quick access</h2>

    <div class="quick-grid">
        <section>
            <h3>Medication rounds</h3>
            <p>Review scheduled medications and record administration outcomes.</p>
            <a class="button" href="dispensing.php">Open medication rounds</a>
        </section>

        <section>
            <h3>Patient records</h3>
            <p>Browse, search and update resident information.</p>
            <a class="button" href="patient_search.php">View patients</a>
        </section>

        <section>
            <h3>Medication reports</h3>
            <p>Review historical and current medication administration records.</p>
            <a class="button" href="medication_report.php">View reports</a>
        </section>
    </div>

    <section>
        <h2>System overview</h2>
        <ul class="subtle-list">
            <li>Use <strong>Medication rounds</strong> to administer scheduled doses.</li>
            <li>Use <strong>Patients</strong> and <strong>Add patient</strong> to manage resident records.</li>
            <li>Use <strong>Reports</strong> to review medication administration activity.</li>
        </ul>
    </section>

    <?php require __DIR__ . '/footer.php'; ?>

</body>
</html>