<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_login();

$title = 'Team Management';
$active = 'teams';
$q = trim((string) get('q'));
$viewId = (int) get('id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) post('action');
    try {
        $fields = [trim((string) post('team_name')), trim((string) post('department')), trim((string) post('contact_no'))];
        if ($action === 'create') {
            exec_stmt('INSERT INTO investigation_team (team_name, department, contact_no) VALUES (?,?,?)', $fields);
            flash('Team created.');
        } elseif ($action === 'update') {
            exec_stmt('UPDATE investigation_team SET team_name=?, department=?, contact_no=? WHERE team_id=?', [...$fields, (int) post('team_id')]);
            flash('Team updated.');
        } elseif ($action === 'delete') {
            exec_stmt('DELETE FROM investigation_team WHERE team_id=?', [(int) post('team_id')]);
            flash('Team deleted.');
        }
    } catch (Throwable $e) {
        safe_error($e);
    }
    redirect(base_url('modules/teams/index.php'));
}

$sql = 'SELECT t.*, COUNT(c.case_id) AS case_count
        FROM investigation_team t
        LEFT JOIN `case` c ON c.team_id = t.team_id
        WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (LOWER(t.team_name) LIKE LOWER(?) OR LOWER(t.department) LIKE LOWER(?))';
    $params[] = pager_like($q);
    $params[] = pager_like($q);
}
$sql .= ' GROUP BY t.team_id ORDER BY t.team_name';
$rows = fetch_all($sql, $params);
$detail = $viewId ? fetch_one('SELECT * FROM investigation_team WHERE team_id=?', [$viewId]) : null;
$form = get('open') === '1' ? $detail : null;
$cases = $viewId ? fetch_all('SELECT case_id, title, status FROM `case` WHERE team_id=? ORDER BY date_opened DESC', [$viewId]) : [];

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get"><input name="q" placeholder="Search team or department" value="<?= h($q) ?>"><button class="btn" type="submit">Search</button></form>
    <button class="btn primary" data-open-modal="itemModal" type="button">Add team</button>
</div>
<?php if ($detail): ?>
<section class="panel">
    <h2><?= h($detail['team_name']) ?></h2>
    <p><?= h($detail['department']) ?> · <?= h($detail['contact_no']) ?></p>
    <h3>Assigned cases</h3>
    <?php if (!$cases): ?><p class="empty">No cases assigned to this team.</p>
    <?php else: ?><ul><?php foreach ($cases as $c): ?><li>#<?= (int) $c['case_id'] ?> <?= h($c['title']) ?> (<?= h($c['status']) ?>)</li><?php endforeach; ?></ul><?php endif; ?>
</section>
<?php endif; ?>
<section class="panel">
<?php if (!$rows): ?><p class="empty">No teams found.</p>
<?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Team</th><th>Department</th><th>Contact</th><th>Cases</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= (int) $row['team_id'] ?></td>
    <td><?= h($row['team_name']) ?></td>
    <td><?= h($row['department']) ?></td>
    <td><?= h($row['contact_no']) ?></td>
    <td><?= (int) $row['case_count'] ?></td>
    <td class="row-actions">
        <a class="btn small" href="?id=<?= (int) $row['team_id'] ?>">Cases</a>
        <a class="btn small" href="?id=<?= (int) $row['team_id'] ?>&open=1">Edit</a>
        <form method="post" data-confirm="Delete this team? Cases will keep records with team unset if allowed.">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="team_id" value="<?= (int) $row['team_id'] ?>">
            <button class="btn small danger">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
<div class="modal" id="itemModal">
<form class="modal-card" method="post" data-loading>
    <h2><?= $form ? 'Edit team' : 'Add team' ?></h2>
    <input type="hidden" name="action" value="<?= $form ? 'update' : 'create' ?>">
    <?php if ($form): ?><input type="hidden" name="team_id" value="<?= (int) $form['team_id'] ?>"><?php endif; ?>
    <div class="stack">
        <label>Team name <input name="team_name" required value="<?= h($form['team_name'] ?? '') ?>"></label>
        <label>Department <input name="department" required value="<?= h($form['department'] ?? '') ?>"></label>
        <label>Contact <input name="contact_no" required value="<?= h($form['contact_no'] ?? '') ?>"></label>
    </div>
    <div class="modal-actions"><button class="btn" type="button" data-close-modal>Cancel</button><button class="btn primary">Save</button></div>
</form>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
