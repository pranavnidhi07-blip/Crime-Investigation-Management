<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

function run_sql_file(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Could not read ' . $path);
    }
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
}

$defaultPasswords = [
    'kevin.m@example.com' => 'Admin@123',
    'neha.sharma@cims.gov' => 'Neha@123',
    'rahul.iyer@cims.gov' => 'Rahul@123',
    'priya.nair@cims.gov' => 'Priya@123',
    'vikram.joshi@cims.gov' => 'Vikram@123',
    'pranav@dev.com' => 'Pass1234',
    'dev@cyber.com' => 'Dev@123',
    'guru@deep.com' => 'Guru@123',
];

try {
    $server = db_server();
    run_sql_file($server, __DIR__ . '/schema.sql');

    $pdo = db();
    $pdo->beginTransaction();

    $pdo->exec("INSERT INTO location (address, city, state, description) VALUES
        ('12 Marine Drive Precinct', 'Mumbai', 'Maharashtra', 'Downtown waterfront beat'),
        ('88 MG Road Junction', 'Bengaluru', 'Karnataka', 'Commercial district'),
        ('5 Connaught Place', 'New Delhi', 'Delhi', 'Central business area'),
        ('21 Marina Warehouse', 'Chennai', 'Tamil Nadu', 'Port storage zone')");

    $pdo->exec("INSERT INTO investigation_team (team_name, department, contact_no) VALUES
        ('Cyber Cell Alpha', 'Cyber Crime', '022-555-0101'),
        ('Homicide Unit 4', 'Crime Branch', '022-555-0102'),
        ('Narcotics Task Force', 'Narcotics', '080-555-0103')");

    $insOfficer = $pdo->prepare('INSERT INTO officer (name, `rank`, department, email, password_hash) VALUES (?,?,?,?,?)');
    $officers = [
        ['Aarav Mehta', 'Inspector', 'Crime Branch', 'kevin.m@example.com'],
        ['Neha Sharma', 'Sub-Inspector', 'Cyber Crime', 'neha.sharma@cims.gov'],
        ['Rahul Iyer', 'DSP', 'Crime Branch', 'rahul.iyer@cims.gov'],
        ['Priya Nair', 'Constable', 'Narcotics', 'priya.nair@cims.gov'],
        ['Vikram Joshi', 'Inspector', 'Cyber Crime', 'vikram.joshi@cims.gov'],
    ];
    foreach ($officers as $o) {
        $email = $o[3];
        $defaultPassword = $defaultPasswords[$email] ?? 'Admin@123';
        $insOfficer->execute([$o[0], $o[1], $o[2], $email, password_hash($defaultPassword, PASSWORD_DEFAULT)]);
    }

    $pdo->exec("INSERT INTO `case` (title, description, status, date_opened, officer_id, team_id, location_id) VALUES
        ('Warehouse burglary', 'Night-time break-in at bonded warehouse.', 'Closed', '2026-01-12', 1, 2, 4),
        ('Phishing syndicate', 'Coordinated email fraud targeting bank customers.', 'Under Investigation', '2026-03-04', 2, 1, 2),
        ('Harbour assault', 'Victim assaulted near marina walkway.', 'Open', '2026-06-18', 3, 2, 1),
        ('Drug seizure ring', 'Multi-city narcotics distribution.', 'Under Investigation', '2026-04-22', 4, 3, 2),
        ('Office laptop theft', 'Laptops stolen from co-working space.', 'Closed', '2025-11-09', 5, 1, 3),
        ('Hit-and-run inquiry', 'Unidentified vehicle fled the scene.', 'Open', '2026-07-01', 1, 2, 3)");

    $pdo->exec("INSERT INTO victim (case_id, name, gender, contact_no) VALUES
        (1, 'Karan Desai', 'Male', '9876500011'),
        (3, 'Meera Kapoor', 'Female', '9876500022'),
        (4, 'Sanjay Pillai', 'Male', '9876500033'),
        (6, 'Ananya Rao', 'Female', '9876500044')");

    $pdo->exec("INSERT INTO suspect (name, age, gender, criminal_record, victim_id) VALUES
        ('Rakesh Bane', 34, 'Male', 'Prior burglary conviction', 1),
        ('Lina Fernandes', 29, 'Female', 'Fraud watchlist', NULL),
        ('Imran Qureshi', 41, 'Male', 'Assault charge 2022', 2),
        ('Dev Patel', 26, 'Male', 'None', 3),
        ('Sonia Dutt', 38, 'Female', 'Traffic offences', 4)");

    $pdo->exec("INSERT INTO evidence (`type`, description, status, collected_date, case_id, location_id) VALUES
        ('CCTV clip', 'Loading-bay camera footage', 'Archived', '2026-01-13', 1, 4),
        ('Laptop', 'Phishing kit recovered from rented flat', 'In Analysis', '2026-03-08', 2, 2),
        ('Blood sample', 'Collected from walkway railing', 'In Analysis', '2026-06-18', 3, 1),
        ('Packet', 'Sealed narcotics packet', 'Collected', '2026-04-22', 4, 2),
        ('Fingerprint', 'Lifted from office desk', 'Archived', '2025-11-09', 5, 3),
        ('Skid mark photo', 'Intersection photograph', 'Collected', '2026-07-01', 6, 3)");

    $pdo->exec("INSERT INTO case_suspect (case_id, suspect_id) VALUES
        (1, 1), (2, 2), (2, 5), (3, 3), (4, 4), (4, 2), (6, 5)");

    $pdo->exec("INSERT INTO case_officer (case_id, officer_id) VALUES
        (2, 5), (3, 1), (4, 3), (6, 3)");

    $pdo->commit();
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem">';
    echo '<h1>CIMS installed</h1>';
    echo '<p>Database <strong>crime_investigation</strong> created with sample data.</p>';
    echo '<p>Login: <code>kevin.m@example.com</code> / <code>Admin@123</code></p>';
    echo '<p><a href="../index.php">Go to login</a></p>';
    echo '</body></html>';
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo '<pre>Install failed: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
