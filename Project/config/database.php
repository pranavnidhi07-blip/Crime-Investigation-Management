<?php
declare(strict_types=1);

$dbHost = getenv('CIMS_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('CIMS_DB_NAME') ?: 'crime_investigation';
$dbUser = getenv('CIMS_DB_USER') ?: 'root';
$dbPass = getenv('CIMS_DB_PASS') !== false ? getenv('CIMS_DB_PASS') : '';

function db(): PDO
{
    static $pdo = null;
    global $dbHost, $dbName, $dbUser, $dbPass;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function db_server(): PDO
{
    global $dbHost, $dbUser, $dbPass;
    $dsn = "mysql:host={$dbHost};charset=utf8mb4";
    return new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
