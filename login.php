<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

$error = '';
$username = '';

if (!empty($_SESSION['practitioner_id']) && time() - ($_SESSION['last_activity'] ?? 0) <= 1800) {
    redirect_to('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $error = 'Invalid username or password.';

    if ($username !== '' && strlen($username) <= 30 && $password !== '' && strlen($password) <= 4096) {
        $stmt = $conn->prepare(
            'SELECT practitioner_id, full_name, password_hash, role
             FROM practitioner
             WHERE username = ?'
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION = [
                'practitioner_id' => (int)$user['practitioner_id'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'last_activity' => time()
            ];

            redirect_to('index.php');
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | CareCode</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

<div class="login-shell">
    <aside class="login-aside">
        <a class="brand" href="login.php">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-text">
                <span class="brand-name">CareCode</span>
                <span class="brand-tagline">Medication Management</span>
            </span>
        </a>

        <h1>Aged-care medication workspace</h1>
        <p>
            A simple practitioner portal for resident records, medication rounds
            and administration reporting.
        </p>

        <ul class="login-points">
            <li>Secure practitioner login</li>
            <li>Medication round recording</li>
            <li>Patient information management</li>
            <li>Medication administration reporting</li>
        </ul>

        <div class="demo-box">
            <strong>Course project</strong><br>
            BIOM9450 Major Project
        </div>
    </aside>

    <main class="login-panel">
        <p class="eyebrow">PRACTITIONER ACCESS</p>
        <h2>Sign in</h2>
        <p class="muted">Enter your username and password to continue.</p>

        <?php if ($error): ?>
            <p role="alert"><?= h($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

            <label for="username">Username</label>
            <input
                id="username"
                name="username"
                maxlength="30"
                autocomplete="username"
                required
                value="<?= h($username) ?>"
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Sign in</button>
        </form>
    </main>
</div>

</body>
</html>