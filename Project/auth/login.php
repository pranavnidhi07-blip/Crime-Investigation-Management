<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('index.php'));
}

$email = trim((string) post('email'));
$password = (string) post('password');

if ($email === '' || $password === '') {
    flash('Email and password are required.', 'error');
    redirect(base_url('index.php'));
}

try {
    $user = fetch_one(
        'SELECT officer_id, name, `rank`, department, email, password_hash FROM officer WHERE email = ?',
        [$email]
    );
} catch (Throwable $e) {
    error_log('Login query failed: ' . $e->getMessage());
    flash('Database is not ready. Run sql/install.php first.', 'error');
    redirect(base_url('index.php'));
}

if (!$user || !password_verify($password, $user['password_hash'])) {
    flash('Invalid email or password.', 'error');
    redirect(base_url('index.php'));
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'officer_id' => (int) $user['officer_id'],
    'name' => $user['name'],
    'rank' => $user['rank'],
    'department' => $user['department'],
    'email' => $user['email'],
];

flash('Welcome back, ' . $_SESSION['user']['name'] . '.');
redirect(base_url('dashboard.php'));
