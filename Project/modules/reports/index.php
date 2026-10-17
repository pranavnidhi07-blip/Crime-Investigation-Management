<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_login();
redirect(base_url('dashboard.php'));

$title = 'Reports and Analytics';
$active = 'reports';
$userId = current_user_id();
$selectedCase = (int) get('case_id');

$caseAccess = '(c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?))';
$byStatus = fetch_all('SELECT c.status, COUNT(*) AS total FROM `case` c WHERE ' . $caseAccess . ' GROUP BY c.status ORDER BY total DESC', [$userId, $userId]);
$byOfficer = fetch_all(
    'SELECT o.name, COUNT(c.case_id) AS total
     FROM officer o
     LEFT JOIN `case` c ON c.officer_id = o.officer_id
     WHERE (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?))
     GROUP BY o.officer_id, o.name
     ORDER BY total DESC, o.name', [$userId, $userId]
);
$multiSuspects = fetch_all(
    'SELECT s.name, COUNT(*) AS cases
     FROM case_suspect cs
     JOIN suspect s ON s.suspect_id = cs.suspect_id
     JOIN `case` c ON c.case_id = cs.case_id
     WHERE (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?))
     GROUP BY s.suspect_id, s.name
     HAVING COUNT(*) > 1
     ORDER BY cases DESC', [$userId, $userId]
);
$caseEvidence = $selectedCase
    ? fetch_all('SELECT e.evidence_id, e.type, e.status, e.collected_date FROM evidence e JOIN `case` c ON c.case_id = e.case_id WHERE e.case_id = ? AND (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?)) ORDER BY e.collected_date', [$selectedCase, $userId, $userId])
    : [];
$byCity = fetch_all(
    'SELECT l.city, COUNT(*) AS total
     FROM `case` c
     INNER JOIN location l ON l.location_id = c.location_id
     WHERE (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?))
     GROUP BY l.city
     ORDER BY total DESC', [$userId, $userId]
);
$byTeam = fetch_all(
    'SELECT t.team_name, COUNT(c.case_id) AS total
     FROM investigation_team t
     LEFT JOIN `case` c ON c.team_id = t.team_id
     WHERE (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?))
     GROUP BY t.team_id, t.team_name
     ORDER BY total DESC', [$userId, $userId]
);
$byMonth = fetch_all(
    "SELECT DATE_FORMAT(c.date_opened, '%Y-%m') AS month, COUNT(*) AS total
     FROM `case` c
     WHERE (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?))
     GROUP BY DATE_FORMAT(c.date_opened, '%Y-%m')
     ORDER BY month", [$userId, $userId]
);
$evidenceStatus = fetch_all('SELECT e.status, COUNT(*) AS total FROM evidence e JOIN `case` c ON c.case_id = e.case_id WHERE (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?)) GROUP BY e.status', [$userId, $userId]);
$hotLocations = fetch_all(
    'SELECT l.city, l.address, COUNT(c.case_id) AS total
     FROM location l
     LEFT JOIN `case` c ON c.location_id = l.location_id
     WHERE (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?))
     GROUP BY l.location_id, l.city, l.address
     ORDER BY total DESC
     LIMIT 5', [$userId, $userId]
);
$noEvidence = fetch_all(
    'SELECT c.case_id, c.title, c.status
     FROM `case` c
     LEFT JOIN evidence e ON e.case_id = c.case_id
     WHERE (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?))
       AND e.evidence_id IS NULL', [$userId, $userId]
);
$aboveAverage = fetch_all(
    'SELECT o.name, COUNT(c.case_id) AS total
     FROM officer o
     JOIN `case` c ON c.officer_id = o.officer_id
     WHERE c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?)
     GROUP BY o.officer_id, o.name
     HAVING COUNT(c.case_id) > (
         SELECT AVG(cnt) FROM (
             SELECT COUNT(*) AS cnt FROM `case` WHERE officer_id IS NOT NULL GROUP BY officer_id
         ) AS per_officer
     )
     ORDER BY total DESC', [$userId, $userId]
);
$distinctDepts = fetch_all('SELECT DISTINCT department FROM officer WHERE officer_id = ? OR officer_id IN (SELECT DISTINCT co.officer_id FROM case_officer co JOIN `case` c ON c.case_id = co.case_id WHERE c.officer_id = ? OR co.officer_id = ?) ORDER BY department', [$userId, $userId, $userId]);
$cases = options('SELECT c.case_id, c.title FROM `case` c WHERE (c.officer_id = ? OR EXISTS (SELECT 1 FROM case_officer co WHERE co.case_id = c.case_id AND co.officer_id = ?)) ORDER BY c.title', 'case_id', 'title', [$userId, $userId]);

function report_table(array $rows, array $headers): void
{
    if (!$rows) {
        echo '<p class="empty">No rows for this query.</p>';
        return;
    }
    echo '<div class="table-wrap"><table><thead><tr>';
    foreach ($headers as $h) {
        echo '<th>' . h($h) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach (array_values($row) as $cell) {
            echo '<td>' . h((string) $cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<p class="muted">Live MySQL results. Use this page in the viva to show joins, aggregates, GROUP BY/HAVING, subqueries, and outer joins.</p>

<div class="split">
    <section class="panel">
        <h2>Cases by status</h2>
        <?php report_table($byStatus, ['Status', 'Total']); ?>
    </section>
    <section class="panel">
        <h2>Cases by officer</h2>
        <?php report_table($byOfficer, ['Officer', 'Cases']); ?>
    </section>
</div>
<div class="split">
    <section class="panel">
        <h2>Suspects in multiple cases</h2>
        <?php report_table($multiSuspects, ['Suspect', 'Cases']); ?>
    </section>
    <section class="panel">
        <h2>Evidence for a selected case</h2>
        <form method="get" class="toolbar">
            <select name="case_id">
                <option value="">Select a case</option>
                <?php foreach ($cases as $id => $name): ?>
                    <option value="<?= h($id) ?>" <?= selected($selectedCase, $id) ?>><?= h($name) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn">Run</button>
        </form>
        <?php report_table($caseEvidence, ['ID', 'Type', 'Status', 'Date']); ?>
    </section>
</div>
<div class="split">
    <section class="panel">
        <h2>Cases by city</h2>
        <?php report_table($byCity, ['City', 'Total']); ?>
    </section>
    <section class="panel">
        <h2>Cases by team</h2>
        <?php report_table($byTeam, ['Team', 'Total']); ?>
    </section>
</div>
<div class="split">
    <section class="panel">
        <h2>Cases opened per month</h2>
        <?php report_table($byMonth, ['Month', 'Total']); ?>
    </section>
    <section class="panel">
        <h2>Evidence by status</h2>
        <?php report_table($evidenceStatus, ['Status', 'Total']); ?>
    </section>
</div>
<div class="split">
    <section class="panel">
        <h2>Locations with most cases</h2>
        <?php report_table($hotLocations, ['City', 'Address', 'Cases']); ?>
    </section>
    <section class="panel">
        <h2>Cases with no evidence (LEFT JOIN)</h2>
        <?php report_table($noEvidence, ['ID', 'Title', 'Status']); ?>
    </section>
</div>
<section class="panel">
    <h2>Officers above average caseload (subquery)</h2>
    <?php report_table($aboveAverage, ['Officer', 'Cases']); ?>
</section>
<section class="panel">
    <h2>DISTINCT departments</h2>
    <?php report_table($distinctDepts, ['Department']); ?>
    <p class="hint">Additional CREATE / ALTER / DROP / LIKE / BETWEEN examples are in <code>sql/demo_queries.sql</code>.</p>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
