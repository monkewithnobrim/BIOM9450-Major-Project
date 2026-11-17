<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<p>PHP received the patient form.</p>';
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