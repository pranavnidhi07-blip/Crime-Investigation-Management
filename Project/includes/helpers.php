<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_user_id(): int
{
    return (int) (current_user()['officer_id'] ?? 0);
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('Please log in to continue.', 'error');
        redirect(base_url('index.php'));
    }
}

function base_url(string $path = ''): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $root = str_replace('\\', '/', dirname($script));
    if (str_contains($root, '/modules/')) {
        $root = preg_replace('#/modules/.*$#', '', $root) ?? $root;
    }
    if (str_contains($root, '/auth')) {
        $root = preg_replace('#/auth.*$#', '', $root) ?? $root;
    }
    if (str_contains($root, '/sql')) {
        $root = preg_replace('#/sql.*$#', '', $root) ?? $root;
    }
    $root = rtrim($root, '/');
    return $root . '/' . ltrim($path, '/');
}

function post(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = '')
{
    return $_GET[$key] ?? $default;
}

function fetch_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function exec_stmt(string $sql, array $params = []): void
{
    db()->prepare($sql)->execute($params);
}

function options(string $sql, string $id, string $label, array $params = []): array
{
    $rows = fetch_all($sql, $params);
    $out = [];
    foreach ($rows as $row) {
        $out[(string) $row[$id]] = $row[$label];
    }
    return $out;
}

function selected($current, $value): string
{
    return (string) $current === (string) $value ? 'selected' : '';
}

function current_user_case_ids(): array
{
    $userId = current_user_id();
    if ($userId <= 0) {
        return [];
    }

    $rows = fetch_all(
        'SELECT case_id FROM `case` WHERE officer_id = ?
         UNION
         SELECT case_id FROM case_officer WHERE officer_id = ?
         ORDER BY case_id',
        [$userId, $userId]
    );

    return array_map(static fn (array $row): int => (int) $row['case_id'], $rows);
}

function case_access_clause(string $alias = 'c'): string
{
    return "({$alias}.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = {$alias}.case_id AND co.officer_id = ?))";
}

function case_access_params(?int $userId = null): array
{
    $userId = $userId ?? current_user_id();
    return [$userId, $userId];
}

function pager_like(string $term): string
{
    return '%' . $term . '%';
}

function safe_error(Throwable $e): void
{
    $msg = $e->getMessage();
    if (str_contains($msg, 'foreign key') || str_contains($msg, 'Integrity constraint')) {
        flash('Action blocked by referential integrity. Related records still exist.', 'error');
        return;
    }
    flash('Request failed. Check the form values and try again.', 'error');
}
