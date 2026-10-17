<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_login();

$title = 'Victim Management';
$active = 'victims';
$q = trim((string) get('q'));
$viewId = (int) get('id');
$userId = current_user_id();
$caseAccess = case_access_clause('c');
$caseParams = case_access_params($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) post('action');
    try {
        $fields = [
            post('case_id') !== '' ? (int) post('case_id') : null,
            trim((string) post('name')),
            (string) post('gender'),
            trim((string) post('contact_no')),
        ];
        if ($action === 'create') {
            exec_stmt('INSERT INTO victim (case_id, name, gender, contact_no) VALUES (?,?,?,?)', $fields);
            flash('Victim added.');
        } elseif ($action === 'update') {
            exec_stmt('UPDATE victim SET case_id=?, name=?, gender=?, contact_no=? WHERE victim_id=?', [...$fields, (int) post('victim_id')]);
            flash('Victim updated.');
        } elseif ($action === 'delete') {
            exec_stmt('DELETE FROM victim WHERE victim_id=?', [(int) post('victim_id')]);
            flash('Victim deleted.');
        }
    } catch (Throwable $e) {
        safe_error($e);
    }
    redirect(base_url('modules/victims/index.php'));
}

$sql = 'SELECT v.*, c.title AS case_title
        FROM victim v
        LEFT JOIN `case` c ON c.case_id = v.case_id
        WHERE v.case_id IS NULL OR EXISTS (
            SELECT 1 FROM `case` c2
            WHERE c2.case_id = v.case_id AND ' . case_access_clause('c2') . '
        )';
$params = $caseParams;
if ($q !== '') {
    $sql .= ' AND (LOWER(v.name) LIKE LOWER(?) OR LOWER(v.contact_no) LIKE LOWER(?) OR CAST(v.victim_id AS CHAR) LIKE ?)';
    $params[] = pager_like($q);
    $params[] = pager_like($q);
    $params[] = pager_like($q);
}
$sql .= ' ORDER BY v.name';
$rows = fetch_all($sql, $params);
$cases = fetch_all('SELECT c.case_id, c.title FROM `case` c WHERE ' . $caseAccess . ' ORDER BY c.title', $caseParams);
$detail = $viewId ? fetch_one(
    'SELECT v.*, c.title AS case_title FROM victim v LEFT JOIN `case` c ON c.case_id = v.case_id WHERE v.victim_id=? AND (v.case_id IS NULL OR EXISTS (SELECT 1 FROM `case` c2 WHERE c2.case_id = v.case_id AND ' . case_access_clause('c2') . '))',
    array_merge([$viewId], $caseParams)
) : null;
$form = get('open') === '1' ? $detail : null;
$linkedSuspects = $viewId ? fetch_all('SELECT suspect_id, name FROM suspect WHERE victim_id=?', [$viewId]) : [];

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get"><input name="q" placeholder="Search name, contact, ID" value="<?= h($q) ?>"><button class="btn" type="submit">Search</button></form>
    <button class="btn primary" data-open-modal="itemModal" type="button">Add victim</button>
</div>
<?php if ($detail): ?>
<section class="panel">
    <h2><?= h($detail['name']) ?></h2>
    <p><?= h($detail['gender']) ?> · <?= h($detail['contact_no']) ?></p>
    <p>Related case: <?= h($detail['case_title'] ?? 'Unassigned') ?></p>
    <p>Suspects reporting this victim: <?= $linkedSuspects ? h(implode(', ', array_column($linkedSuspects, 'name'))) : 'None' ?></p>
</section>
<?php endif; ?>
<section class="panel">
<?php if (!$rows): ?><p class="empty">No victims found.</p>
<?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Name</th><th>Gender</th><th>Contact</th><th>Case</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= (int) $row['victim_id'] ?></td>
    <td><?= h($row['name']) ?></td>
    <td><?= h($row['gender']) ?></td>
    <td><?= h($row['contact_no']) ?></td>
    <td><?= h($row['case_title'] ?? '—') ?></td>
    <td class="row-actions">
        <a class="btn small" href="?id=<?= (int) $row['victim_id'] ?>">Details</a>
        <a class="btn small" href="?id=<?= (int) $row['victim_id'] ?>&open=1">Edit</a>
        <form method="post" data-confirm="Delete this victim?">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="victim_id" value="<?= (int) $row['victim_id'] ?>">
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
    <h2><?= $form ? 'Edit victim' : 'Add victim' ?></h2>
    <input type="hidden" name="action" value="<?= $form ? 'update' : 'create' ?>">
    <?php if ($form): ?><input type="hidden" name="victim_id" value="<?= (int) $form['victim_id'] ?>"><?php endif; ?>
    <div class="stack">
        <label>Name <input name="name" required value="<?= h($form['name'] ?? '') ?>"></label>
        <div class="grid-2">
            <label>Gender
                <select name="gender"><?php foreach (['Male', 'Female', 'Other'] as $g): ?><option value="<?= h($g) ?>" <?= selected($form['gender'] ?? 'Female', $g) ?>><?= h($g) ?></option><?php endforeach; ?></select>
            </label>
            <label>Contact <input name="contact_no" required value="<?= h($form['contact_no'] ?? '') ?>"></label>
        </div>
        <label>Related case
            <select name="case_id">
                <option value="">Unassigned</option>
                <?php foreach ($cases as $item): ?><option value="<?= h((string) $item['case_id']) ?>" <?= selected($form['case_id'] ?? '', $item['case_id']) ?>><?= h($item['title']) ?></option><?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="modal-actions"><button class="btn" type="button" data-close-modal>Cancel</button><button class="btn primary">Save</button></div>
</form>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
