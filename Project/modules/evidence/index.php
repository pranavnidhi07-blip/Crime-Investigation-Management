<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_login();

$title = 'Evidence Management';
$active = 'evidence';
$q = trim((string) get('q'));
$status = (string) get('status');
$viewId = (int) get('id');
$userId = current_user_id();
$caseAccess = case_access_clause('c');
$caseParams = case_access_params($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) post('action');
    try {
        $fields = [
            trim((string) post('type')),
            trim((string) post('description')),
            (string) post('status'),
            (string) post('collected_date'),
            (int) post('case_id'),
            (int) post('location_id'),
        ];
        if ($action === 'create') {
            exec_stmt('INSERT INTO evidence (`type`, description, status, collected_date, case_id, location_id) VALUES (?,?,?,?,?,?)', $fields);
            flash('Evidence logged.');
        } elseif ($action === 'update') {
            exec_stmt('UPDATE evidence SET `type`=?, description=?, status=?, collected_date=?, case_id=?, location_id=? WHERE evidence_id=?', [...$fields, (int) post('evidence_id')]);
            flash('Evidence updated.');
        } elseif ($action === 'delete') {
            exec_stmt('DELETE FROM evidence WHERE evidence_id=?', [(int) post('evidence_id')]);
            flash('Evidence deleted.');
        }
    } catch (Throwable $e) {
        safe_error($e);
    }
    redirect(base_url('modules/evidence/index.php'));
}

$sql = 'SELECT e.*, c.title AS case_title, l.city
        FROM evidence e
        JOIN `case` c ON c.case_id = e.case_id
        JOIN location l ON l.location_id = e.location_id
        WHERE ' . $caseAccess;
$params = $caseParams;
if ($q !== '') {
    $sql .= ' AND (LOWER(e.description) LIKE LOWER(?) OR LOWER(e.type) LIKE LOWER(?) OR CAST(e.evidence_id AS CHAR) LIKE ?)';
    $params[] = pager_like($q);
    $params[] = pager_like($q);
    $params[] = pager_like($q);
}
if ($status !== '') {
    $sql .= ' AND LOWER(e.status) = LOWER(?)';
    $params[] = $status;
}
$sql .= ' ORDER BY e.collected_date DESC';
$rows = fetch_all($sql, $params);
$cases = fetch_all('SELECT c.case_id, c.title FROM `case` c WHERE ' . $caseAccess . ' ORDER BY c.title', $caseParams);
$locations = options("SELECT location_id, CONCAT(city, ' — ', address) AS label FROM location ORDER BY city", 'location_id', 'label');
$detail = $viewId ? fetch_one(
    'SELECT e.*, c.title AS case_title, l.address, l.city FROM evidence e JOIN `case` c ON c.case_id=e.case_id JOIN location l ON l.location_id=e.location_id WHERE e.evidence_id=? AND ' . $caseAccess,
    array_merge([$viewId], $caseParams)
) : null;
$form = get('open') === '1' ? $detail : null;

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get">
        <input name="q" placeholder="Search type or description" value="<?= h($q) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['Collected', 'In Analysis', 'Archived'] as $opt): ?><option value="<?= h($opt) ?>" <?= selected($status, $opt) ?>><?= h($opt) ?></option><?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Filter</button>
    </form>
    <button class="btn primary" data-open-modal="itemModal" type="button">Add evidence</button>
</div>
<?php if ($detail): ?>
<section class="panel">
    <h2><?= h($detail['type']) ?></h2>
    <p><?= h($detail['description']) ?></p>
    <p>Status: <?= h($detail['status']) ?> · Collected <?= h($detail['collected_date']) ?></p>
    <p>Case: <?= h($detail['case_title']) ?> · Found at: <?= h($detail['city'] . ', ' . $detail['address']) ?></p>
</section>
<?php endif; ?>
<section class="panel">
<?php if (!$rows): ?><p class="empty">No evidence found.</p>
<?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Type</th><th>Description</th><th>Status</th><th>Date</th><th>Case</th><th>City</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= (int) $row['evidence_id'] ?></td>
    <td><?= h($row['type']) ?></td>
    <td><?= h($row['description']) ?></td>
    <td><span class="badge <?= h(strtolower(str_replace(' ', '-', $row['status']))) ?>"><?= h($row['status']) ?></span></td>
    <td><?= h($row['collected_date']) ?></td>
    <td><?= h($row['case_title']) ?></td>
    <td><?= h($row['city']) ?></td>
    <td class="row-actions">
        <a class="btn small" href="?id=<?= (int) $row['evidence_id'] ?>">View</a>
        <a class="btn small" href="?id=<?= (int) $row['evidence_id'] ?>&open=1">Edit</a>
        <form method="post" data-confirm="Delete this evidence record?">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="evidence_id" value="<?= (int) $row['evidence_id'] ?>">
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
    <h2><?= $form ? 'Edit evidence' : 'Add evidence' ?></h2>
    <input type="hidden" name="action" value="<?= $form ? 'update' : 'create' ?>">
    <?php if ($form): ?><input type="hidden" name="evidence_id" value="<?= (int) $form['evidence_id'] ?>"><?php endif; ?>
    <div class="stack">
        <div class="grid-2">
            <label>Type <input name="type" required value="<?= h($form['type'] ?? '') ?>"></label>
            <label>Status
                <select name="status"><?php foreach (['Collected', 'In Analysis', 'Archived'] as $opt): ?><option value="<?= h($opt) ?>" <?= selected($form['status'] ?? 'Collected', $opt) ?>><?= h($opt) ?></option><?php endforeach; ?></select>
            </label>
        </div>
        <label>Description <input name="description" required value="<?= h($form['description'] ?? '') ?>"></label>
        <label>Collected date <input type="date" name="collected_date" required value="<?= h($form['collected_date'] ?? date('Y-m-d')) ?>"></label>
        <div class="grid-2">
            <label>Case
                <select name="case_id" required>
                    <?php foreach ($cases as $item): ?><option value="<?= h((string) $item['case_id']) ?>" <?= selected($form['case_id'] ?? '', $item['case_id']) ?>><?= h($item['title']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Found at
                <select name="location_id" required>
                    <?php foreach ($locations as $id => $name): ?><option value="<?= h($id) ?>" <?= selected($form['location_id'] ?? '', $id) ?>><?= h($name) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
    </div>
    <div class="modal-actions"><button class="btn" type="button" data-close-modal>Cancel</button><button class="btn primary">Save</button></div>
</form>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
