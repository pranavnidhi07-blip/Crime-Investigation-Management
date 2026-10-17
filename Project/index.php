<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

if (current_user()) {
    redirect(base_url('dashboard.php'));
}

$error = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CIMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(base_url('assets/css/style.css')) ?>">
</head>
<body class="login-body">
<div class="login-bg" aria-hidden="true"></div>
<div class="login-card">
    <div class="brand login-brand">
        <span class="brand-mark">CI</span>
        <div>
            <strong>CIMS</strong>
            <small>Crime Investigation Management System</small>
        </div>
    </div>

    <div class="login-header">
        <h1>Officer Login</h1>
        <p>Secure access to investigation records and case intelligence.</p>
    </div>

    <?php if ($error): ?>
        <div class="toast <?= h($error['type']) ?>"><?= h($error['message']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= h(base_url('auth/login.php')) ?>" class="stack" autocomplete="off">
        <label>
            <span>Email</span>
            <input type="email" name="email" required autocomplete="username" placeholder="name@department.gov" value="">
        </label>

        <label>
            <span>Password</span>
            <div class="password-wrap">
                <input id="loginPassword" type="password" name="password" required autocomplete="current-password" placeholder="Enter your password" value="">
                <button type="button" class="link-btn" data-toggle-password="loginPassword">Show</button>
            </div>
        </label>

        <div class="login-row">
            <label class="checkbox-wrap">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me</span>
            </label>
        </div>

        <button class="btn primary" type="submit">Login</button>
    </form>

    <div class="auth-divider"><span>Need an account?</span></div>
    <a class="btn secondary" href="<?= h(base_url('auth/register.php')) ?>">Create New Account</a>
</div>
<script src="<?= h(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
