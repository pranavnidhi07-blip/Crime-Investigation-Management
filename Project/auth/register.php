<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';

if (current_user()) {
    redirect(base_url('dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) post('name'));
    $email = trim((string) post('email'));
    $password = (string) post('password');
    $confirmPassword = (string) post('confirm_password');
    $rank = trim((string) post('rank'));
    $department = trim((string) post('department'));

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '' || $rank === '' || $department === '') {
        flash('All fields are required.', 'error');
        redirect(base_url('auth/register.php'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Please enter a valid email address.', 'error');
        redirect(base_url('auth/register.php'));
    }

    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        flash('Password must be at least 8 characters long and include letters and numbers.', 'error');
        redirect(base_url('auth/register.php'));
    }

    if ($password !== $confirmPassword) {
        flash('Passwords do not match.', 'error');
        redirect(base_url('auth/register.php'));
    }

    try {
        $existing = fetch_one('SELECT officer_id FROM officer WHERE email = ?', [$email]);
        if ($existing) {
            flash('An account with this email already exists. Please log in instead.', 'error');
            redirect(base_url('auth/register.php'));
        }

        exec_stmt(
            'INSERT INTO officer (name, `rank`, department, email, password_hash) VALUES (?, ?, ?, ?, ?)',
            [$name, $rank, $department, $email, password_hash($password, PASSWORD_DEFAULT)]
        );

        flash('Account created successfully. Please log in to continue.', 'success');
        redirect(base_url('index.php'));
    } catch (Throwable $e) {
        error_log('Registration failed: ' . $e->getMessage());
        flash('Unable to create your account at the moment. Please try again.', 'error');
        redirect(base_url('auth/register.php'));
    }
}

$error = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | CIMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(base_url('assets/css/style.css')) ?>">
</head>
<body class="login-body">
<div class="login-bg" aria-hidden="true"></div>
<div class="login-card register-card">
    <div class="brand login-brand">
        <span class="brand-mark">CI</span>
        <div>
            <strong>CIMS</strong>
            <small>New Officer Registration</small>
        </div>
    </div>

    <div class="login-header">
        <h1>Create Account</h1>
        <p>Register a new investigator profile for case access.</p>
    </div>

    <?php if ($error): ?>
        <div class="toast <?= h($error['type']) ?>"><?= h($error['message']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= h(base_url('auth/register.php')) ?>" class="stack" autocomplete="off">
        <div class="grid-2">
            <label>
                <span>Full Name</span>
                <input type="text" name="name" required placeholder="Officer name" autocomplete="name" value="">
            </label>
            <label>
                <span>Rank</span>
                <input type="text" name="rank" required placeholder="Inspector / DSP" value="">
            </label>
        </div>

        <label>
            <span>Department</span>
            <input type="text" name="department" required placeholder="Crime Branch / Cyber Crime" value="">
        </label>

        <label>
            <span>Email</span>
            <input type="email" name="email" required placeholder="name@department.gov" autocomplete="username" value="">
        </label>

        <label>
            <span>Password</span>
            <div class="password-wrap">
                <input id="regPassword" type="password" name="password" required placeholder="Min 8 chars, letters + number" autocomplete="new-password" value="">
                <button type="button" class="link-btn" data-toggle-password="regPassword">Show</button>
            </div>
        </label>

        <label>
            <span>Confirm Password</span>
            <div class="password-wrap">
                <input id="regConfirmPassword" type="password" name="confirm_password" required placeholder="Re-enter password" autocomplete="new-password" value="">
                <button type="button" class="link-btn" data-toggle-password="regConfirmPassword">Show</button>
            </div>
        </label>

        <button class="btn primary" type="submit">Create Account</button>
    </form>

    <div class="auth-divider"><span>Already registered?</span></div>
    <a class="btn secondary" href="<?= h(base_url('index.php')) ?>">Back to Login</a>
</div>
<script src="<?= h(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
