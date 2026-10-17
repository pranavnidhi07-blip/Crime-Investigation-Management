<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_login();

$title = 'Officer Management';
$active = 'officers';
$q = trim((string) get('q'));
$department = (string) get('department');
$rank = (string) get('rank');
$viewId = (int) get('id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(base_url('modules/officers/index.php'));
}

$sql = 'SELECT * FROM officer WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (LOWER(name) LIKE LOWER(?) OR LOWER(email) LIKE LOWER(?) OR CAST(officer_id AS CHAR) LIKE ?)';
    $params[] = pager_like($q);
    $params[] = pager_like($q);
    $params[] = pager_like($q);
}
if ($department !== '') {
    $sql .= ' AND LOWER(department) = LOWER(?)';
    $params[] = $department;
}
if ($rank !== '') {
    $sql .= ' AND LOWER(`rank`) = LOWER(?)';
    $params[] = $rank;
}
$sql .= ' ORDER BY name';
$rows = fetch_all($sql, $params);
$departments = array_column(fetch_all('SELECT DISTINCT department FROM officer ORDER BY department'), 'department');
$ranks = array_column(fetch_all('SELECT DISTINCT `rank` FROM officer ORDER BY `rank`'), 'rank');

$detail = $viewId ? fetch_one('SELECT * FROM officer WHERE officer_id=?', [$viewId]) : null;
$cases = $viewId ? fetch_all(
    'SELECT DISTINCT c.case_id, c.title, c.status
     FROM `case` c
     LEFT JOIN case_officer co ON co.case_id = c.case_id
     WHERE c.officer_id = ? OR co.officer_id = ?
     ORDER BY c.case_id',
    [$viewId, $viewId]
) : [];

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get">
        <input name="q" placeholder="Search name, email, ID" value="<?= h($q) ?>">
        <select name="department">
            <option value="">All departments</option>
            <?php foreach ($departments as $d): ?><option value="<?= h($d) ?>" <?= selected($department, $d) ?>><?= h($d) ?></option><?php endforeach; ?>
        </select>
        <select name="rank">
            <option value="">All ranks</option>
            <?php foreach ($ranks as $r): ?><option value="<?= h($r) ?>" <?= selected($rank, $r) ?>><?= h($r) ?></option><?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Filter</button>
    </form>
</div>
<?php if ($detail): ?>
<section class="panel">
    <h2><?= h($detail['name']) ?></h2>
    <p><?= h($detail['rank']) ?> · <?= h($detail['department']) ?> · <?= h($detail['email']) ?></p>
    <h3>Investigated cases</h3>
    <?php if (!$cases): ?><p class="empty">No cases assigned.</p>
    <?php else: ?>
        <ul><?php foreach ($cases as $c): ?><li>#<?= (int) $c['case_id'] ?> <?= h($c['title']) ?> (<?= h($c['status']) ?>)</li><?php endforeach; ?></ul>
    <?php endif; ?>
</section>
<?php endif; ?>
<section class="panel">
<?php if (!$rows): ?><p class="empty">No officers found.</p>
<?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Name</th><th>Rank</th><th>Department</th><th>Email</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= (int) $row['officer_id'] ?></td>
    <td><?= h($row['name']) ?></td>
    <td><?= h($row['rank']) ?></td>
    <td><?= h($row['department']) ?></td>
    <td><?= h($row['email']) ?></td>
    <td class="row-actions">
        <a class="btn small" href="?id=<?= (int) $row['officer_id'] ?>">Cases</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
