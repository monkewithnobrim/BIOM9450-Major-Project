<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';

$rounds = ['all', 'morning', 'afternoon', 'evening'];
$statuses = ['given', 'refused', 'fasting', 'no stock', 'ceased'];
$date = is_string($_GET['date'] ?? null) ? $_GET['date'] : date('Y-m-d');
$round = is_string($_GET['round'] ?? null) ? $_GET['round'] : 'all';
$patientId = filter_var($_GET['patient_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$error = validate_date($date, 'Date', true) ?: validate_in_list($round, $rounds, 'round');

$patients = [];
$meds = [];
$patient = null;
$photoPath = '';
$photoAvailable = false;
$message = $_SESSION['round_message'] ?? '';
$warning = $_SESSION['round_warning'] ?? '';
unset($_SESSION['round_message'], $_SESSION['round_warning']);

if (!$error) {
    $stmt = $conn->prepare(
        "SELECT p.patient_id, p.first_name, p.last_name, p.room,
                p.date_of_birth, p.photo_path, COUNT(*) AS total,
                SUM(a.status = 'pending') AS pending
         FROM patient p
         JOIN medication_order o ON o.patient_id = p.patient_id
         JOIN medication_administration a ON a.order_id = o.order_id
         WHERE a.scheduled_date = ? AND (? = 'all' OR o.round = ?)
         GROUP BY p.patient_id, p.first_name, p.last_name, p.room,
                  p.date_of_birth, p.photo_path
         ORDER BY p.room, p.last_name"
    );
    $stmt->bind_param('sss', $date, $round, $round);
    $stmt->execute();
    $patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($patients as $p) {
        if ((int) $p['patient_id'] === $patientId) {
            $patient = $p;
            break;
        }
    }

    if ($patient) {
        $stmt = $conn->prepare(
            "SELECT a.admin_id, a.status, a.notes, a.administered_at,
                    m.name, m.strength, m.route, o.dose, o.round,
                    pr.full_name
             FROM medication_administration a
             JOIN medication_order o ON o.order_id = a.order_id
             JOIN medication m ON m.medication_id = o.medication_id
             LEFT JOIN practitioner pr ON pr.practitioner_id = a.practitioner_id
             WHERE a.scheduled_date = ? AND (? = 'all' OR o.round = ?)
                   AND o.patient_id = ?
             ORDER BY FIELD(o.round, 'morning', 'afternoon', 'evening'), m.name, a.admin_id"
        );
        $stmt->bind_param('sssi', $date, $round, $round, $patientId);
        $stmt->execute();
        $meds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $photoPath = $patient['photo_path'] ?? '';
        if (preg_match('~^(photos|patient_photos)/[A-Za-z0-9_.-]+\.(jpg|jpeg|png)$~i', $photoPath)) {
            $photoFile = realpath(__DIR__ . '/' . $photoPath);
            $photoFolder = realpath(__DIR__ . '/' . dirname($photoPath));
            $photoAvailable = $photoFile !== false &&
                $photoFolder !== false &&
                dirname($photoFile) === $photoFolder &&
                is_file($photoFile);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Medication rounds | CareCode</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php require __DIR__ . '/header.php'; ?>

    <p class="eyebrow">ADMINISTRATION</p>
    <h1>Medication rounds</h1>
    <p class="muted">Choose a scheduled date and optionally a round, then select a resident.</p>

    <div id="round-feedback" aria-live="polite">
        <?php if ($message): ?>
            <p role="status"><?= h($message) ?></p>
        <?php endif; ?>
        <?php if ($warning): ?>
            <p role="alert"><?= h($warning) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <p role="alert"><?= h($error) ?></p>
    <?php endif; ?>

    <section>
        <form method="get" class="filter-bar">
            <div>
                <label for="date">Scheduled date</label>
                <input type="date" id="date" name="date" value="<?= h($date) ?>" required>
            </div>
            <div>
                <label for="round">Round (optional)</label>
                <select id="round" name="round">
                    <?php foreach ($rounds as $r): ?>
                        <option value="<?= h($r) ?>" <?= $r === $round ? 'selected' : '' ?>>
                            <?= $r === 'all' ? 'All rounds' : h(ucfirst($r)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Load round</button>
        </form>
    </section>

    <div class="round-layout">
        <section>
            <h2>Residents</h2>
            <?php if (!$patients): ?>
                <p>No scheduled medications for this date and round.</p>
            <?php endif; ?>
            <?php foreach ($patients as $p): ?>
                <a class="resident <?= (int) $p['patient_id'] === $patientId ? 'active' : '' ?>"
                   href="?<?= h(http_build_query([
                       'date' => $date,
                       'round' => $round,
                       'patient_id' => $p['patient_id']
                   ])) ?>">
                    <strong><?= h($p['first_name'] . ' ' . $p['last_name']) ?></strong>
                    <small>Room <?= h($p['room']) ?> · <?= h($p['pending']) ?> of <?= h($p['total']) ?> pending</small>
                </a>
            <?php endforeach; ?>
        </section>

        <section>
            <?php if ($patient): ?>
                <div class="patient-heading">
                    <div>
                        <h2><?= h($patient['first_name'] . ' ' . $patient['last_name']) ?></h2>
                        <p class="muted">
                            ID <?= h($patientId) ?> · Room <?= h($patient['room']) ?> · DOB <?= h($patient['date_of_birth']) ?>
                        </p>
                    </div>
                    <?php if ($photoAvailable): ?>
                        <img class="resident-photo" src="<?= h($photoPath) ?>"
                             alt="<?= h('Photo of ' . $patient['first_name'] . ' ' . $patient['last_name']) ?>">
                    <?php else: ?>
                        <div class="resident-photo photo-placeholder">No photo available</div>
                    <?php endif; ?>
                </div>

                <?php foreach ($meds as $m): ?>
                    <article class="med-card" data-admin-id="<?= h($m['admin_id']) ?>">
                        <div class="med-heading">
                            <h3><?= h($m['name'] . ' ' . $m['strength']) ?></h3>
                            <span class="round-label"><?= h(ucfirst($m['round'])) ?></span>
                        </div>
                        <p>Dose: <strong><?= h($m['dose']) ?></strong> · Route: <?= h(ucfirst($m['route'])) ?></p>
                        <span class="status <?= h(str_replace(' ', '-', $m['status'])) ?>">
                            <?= h(ucfirst($m['status'])) ?>
                        </span>

                        <?php if ($m['status'] === 'pending'): ?>
                            <form method="post" action="dispense_save.php" class="dispense-form">
                                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="admin_id" value="<?= h($m['admin_id']) ?>">
                                <input type="hidden" name="patient_id" value="<?= h($patientId) ?>">
                                <input type="hidden" name="date" value="<?= h($date) ?>">
                                <input type="hidden" name="round" value="<?= h($m['round']) ?>">
                                <input type="hidden" name="view_round" value="<?= h($round) ?>">

                                <label for="status-<?= h($m['admin_id']) ?>">Outcome</label>
                                <select required name="status" id="status-<?= h($m['admin_id']) ?>">
                                    <option value="">Choose outcome</option>
                                    <?php foreach ($statuses as $s): ?>
                                        <option value="<?= h($s) ?>"><?= h(ucfirst($s)) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <label for="notes-<?= h($m['admin_id']) ?>">Notes (optional)</label>
                                <textarea name="notes" maxlength="255" id="notes-<?= h($m['admin_id']) ?>" rows="2"></textarea>
                                <button type="submit">Save outcome</button>
                            </form>
                        <?php else: ?>
                            <p>Recorded by <?= h($m['full_name'] ?? 'Unknown') ?> · <?= h($m['administered_at'] ?? '') ?></p>
                            <p><?= h($m['notes'] ?? '') ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <h2>Select a resident</h2>
                <p>Select a resident from the list to review scheduled doses.</p>
            <?php endif; ?>
        </section>
    </div>

    <?php require __DIR__ . '/footer.php'; ?>
    <script src="script.js" defer></script>
</body>
</html>