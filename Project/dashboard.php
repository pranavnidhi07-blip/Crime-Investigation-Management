<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_login();

$title = 'Dashboard';
$active = 'dashboard';

$userId = current_user_id();
$caseAccess = case_access_clause('c');
$caseParams = case_access_params($userId);

$counts = [
    'cases' => fetch_one(
        'SELECT COUNT(*) AS c FROM `case` c WHERE ' . $caseAccess,
        $caseParams
    )['c'] ?? 0,
    'officers' => fetch_one('SELECT COUNT(*) AS c FROM officer')['c'] ?? 0,
    'suspects' => fetch_one('SELECT COUNT(*) AS c FROM suspect')['c'] ?? 0,
    'victims' => fetch_one(
        'SELECT COUNT(*) AS c FROM victim v WHERE EXISTS (
            SELECT 1 FROM `case` c
            WHERE c.case_id = v.case_id AND ' . $caseAccess . '
        )',
        $caseParams
    )['c'] ?? 0,
    'evidence' => fetch_one(
        'SELECT COUNT(*) AS c FROM evidence e WHERE EXISTS (
            SELECT 1 FROM `case` c
            WHERE c.case_id = e.case_id AND ' . $caseAccess . '
        )',
        $caseParams
    )['c'] ?? 0,
];

$statusRows = fetch_all(
    'SELECT c.status, COUNT(*) AS total FROM `case` c WHERE ' . $caseAccess . ' GROUP BY c.status ORDER BY c.status',
    $caseParams
);
$recent = fetch_all(
    'SELECT c.case_id, c.title, c.status, c.date_opened, o.name AS officer
     FROM `case` c
     LEFT JOIN officer o ON o.officer_id = c.officer_id
     WHERE ' . $caseAccess . '
     ORDER BY c.date_opened DESC, c.case_id DESC
     LIMIT 6',
    $caseParams
);
$activity = fetch_all(
    "SELECT l.city, COUNT(*) AS total
     FROM `case` c
     INNER JOIN location l ON l.location_id = c.location_id
     WHERE " . $caseAccess . "
     GROUP BY l.city
     ORDER BY total DESC",
    $caseParams
);

require __DIR__ . '/includes/header.php';
?>
<section class="stat-grid">
    <?php foreach ([['Cases', $counts['cases'], 'cyan'], ['Officers', $counts['officers'], 'purple'], ['Suspects', $counts['suspects'], 'orange'], ['Victims', $counts['victims'], 'green'], ['Evidence', $counts['evidence'], 'red']] as [$label, $value, $tone]): ?>
        <article class="stat-card <?= h($tone) ?>">
            <span><?= h($label) ?></span>
            <strong data-count="<?= (int) $value ?>">0</strong>
        </article>
    <?php endforeach; ?>
</section>

<div class="split">
    <section class="panel">
        <h2>Case status</h2>
        <canvas id="statusChart" height="220"></canvas>
        <script type="application/json" id="statusData"><?= json_encode($statusRows) ?></script>
    </section>
    <section class="panel">
        <h2>Quick actions</h2>
        <div class="quick-actions">
            <a class="btn primary" href="<?= h(base_url('modules/cases/index.php?open=1')) ?>">Add case</a>
            <a class="btn" href="<?= h(base_url('modules/evidence/index.php?open=1')) ?>">Log evidence</a>
        </div>
        <h3>Cases by city</h3>
        <ul class="plain-list">
            <?php foreach ($activity as $row): ?>
                <li><span><?= h($row['city']) ?></span><strong><?= (int) $row['total'] ?></strong></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<section class="panel">
    <h2>Recent cases</h2>
    <?php if (!$recent): ?>
        <p class="empty">No cases yet. Add the first investigation record.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Opened</th><th>Investigator</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td><?= (int) $row['case_id'] ?></td>
                        <td><?= h($row['title']) ?></td>
                        <td><span class="badge <?= h(strtolower(str_replace(' ', '-', $row['status']))) ?>"><?= h($row['status']) ?></span></td>
                        <td><?= h($row['date_opened']) ?></td>
                        <td><?= h($row['officer'] ?? 'Unassigned') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
