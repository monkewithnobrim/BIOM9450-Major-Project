<?php
require_once __DIR__ . '/db.php';

$result = $conn->query(
    'SELECT patient_id, first_name, last_name, date_of_birth, gender, room, photo_path, admission_date
     FROM patient
     ORDER BY patient_id'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Patient table test</title>
    <style>
        body  { font-family: Arial, sans-serif; margin: 2rem; }
        table { border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px 10px; text-align: left; }
        th    { background: #eee; }
    </style>
</head>
<body>
    <h1>Patients (<?= $result->num_rows ?>)</h1>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Date of birth</th><th>Gender</th>
            <th>Room</th><th>Photo path</th><th>Admitted</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['patient_id']) ?></td>
            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['date_of_birth']) ?></td>
            <td><?= htmlspecialchars($row['gender']) ?></td>
            <td><?= htmlspecialchars($row['room']) ?></td>
            <td><?=