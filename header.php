<?php
require_once __DIR__ . '/auth.php';

$navLinks = [
    'index.php' => 'Overview',
    'dispensing.php' => 'Medication rounds',
    'patient_search.php' => 'Patients',
    'add_patient.php' => 'Add patient',
    'medication_report.php' => 'Reports'
];
?>
<header class="site-header">
    <a class="brand" href="index.php">
        <span class="brand-mark" aria-hidden="true"></span>
        <span class="brand-text">
            <span class="brand-name">CareCode</span>
            <span class="brand-tagline">Medication Management</span>
        </span>
    </a>

    <div class="account">
        <span>Signed in as <strong><?= h($_SESSION['full_name']) ?></strong></span>

        <form method="post" action="logout.php">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <button class="secondary" type="submit">Sign out</button>
        </form>
    </div>
</header>

<nav class="site-nav" aria-label="Main navigation">
    <?php foreach ($navLinks as $url => $label): ?>
        <a href="<?= h($url) ?>"
           <?= basename($_SERVER['SCRIPT_NAME']) === $url ? 'aria-current="page"' : '' ?>>
            <?= h($label) ?>
        </a>
    <?php endforeach; ?>
</nav>

<main class="workspace">