<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_login();

$title = 'Suspect Management';
$active = 'suspects';
$q = trim((string) get('q'));
$viewId = (int) get('id');
$userId = current_user_id();
$caseAccess = case_access_clause('c');
$caseParams = case_access_params($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) post('action');
    try {
        $fields = [
            trim((string) post('name')),
            (int) post('age'),
            (string) post('gender'),
            trim((string) post('criminal_record')),
            post('victim_id') !== '' ? (int) post('victim_id') : null,
        ];
        if ($action === 'create') {
            exec_stmt('INSERT INTO suspect (name, age, gender, criminal_record, victim_id) VALUES (?,?,?,?,?)', $fields);
            flash('Suspect added.');
        } elseif ($action === 'update') {
            exec_stmt('UPDATE suspect SET name=?, age=?, gender=?, criminal_record=?, victim_id=? WHERE suspect_id=?', [...$fields, (int) post('suspect_id')]);
            flash('Suspect updated.');
        } elseif ($action === 'delete') {
            exec_stmt('DELETE FROM suspect WHERE suspect_id=?', [(int) post('suspect_id')]);
            flash('Suspect deleted.');
        }
    } catch (Throwable $e) {
        safe_error($e);
    }
    redirect(base_url('modules/suspects/index.php'));
}

$sql = 'SELECT s.*, v.name AS victim_name
        FROM suspect s
        LEFT JOIN victim v ON v.victim_id = s.victim_id
        WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (LOWER(s.name) LIKE LOWER(?) OR CAST(s.suspect_id AS CHAR) LIKE ?)';
    $params[] = pager_like($q);
    $params[] = pager_like($q);
}
$sql .= ' GROUP BY s.suspect_id ORDER BY s.name';
$rows = fetch_all($sql, $params);
$victims = fetch_all('SELECT victim_id, name FROM victim ORDER BY name');
$detail = $viewId ? fetch_one('SELECT s.*, v.name AS victim_name FROM suspect s LEFT JOIN victim v ON v.victim_id = s.victim_id WHERE s.suspect_id=?', [$viewId]) : null;
$form = get('open') === '1' ? $detail : null;
$cases = $viewId ? fetch_all(
    'SELECT c.case_id, c.title, c.status FROM case_suspect cs JOIN `case` c ON c.case_id = cs.case_id WHERE cs.suspect_id=? AND ' . $caseAccess . ' ORDER BY c.case_id',
    array_merge([$viewId], $caseParams)
) : [];

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get">
        <input name="q" placeholder="Search ID or name" value="<?= h($q) ?>">
        <button class="btn" type="submit">Search</button>
    </form>
    <button class="btn primary" data-open-modal="itemModal" type="button">Add suspect</button>
</div>
<?php if ($detail): ?>
<section class="panel">
    <h2><?= h($detail['name']) ?></h2>
    <p><?= (int) $detail['age'] ?> · <?= h($detail['gender']) ?> · Record: <?= h($detail['criminal_record'] ?: 'None') ?></p>
    <p>Reported victim (ER N:1): <?= h($detail['victim_name'] ?? 'None') ?></p>
    <h3>Involved cases</h3>
    <?php if (!$cases): ?><p class="empty">Not linked to a case yet.</p>
    <?php else: ?><ul><?php foreach ($cases as $c): ?><li>#<?= (int) $c['case_id'] ?> <?= h($c['title']) ?></li><?php endforeach; ?></ul><?php endif; ?>
</section>
<?php endif; ?>
<section class="panel">
<?php if (!$rows): ?><p class="empty">No suspects found.</p>
<?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Record</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= (int) $row['suspect_id'] ?></td>
    <td><?= h($row['name']) ?></td>
    <td><?= (int) $row['age'] ?></td>
    <td><?= h($row['gender']) ?></td>
    <td><?= h($row['criminal_record']) ?></td>
    <td class="row-actions">
        <a class="btn small" href="?id=<?= (int) $row['suspect_id'] ?>">Cases</a>
        <a class="btn small" href="?id=<?= (int) $row['suspect_id'] ?>&open=1">Edit</a>
        <form method="post" data-confirm="Delete this suspect?">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="suspect_id" value="<?= (int) $row['suspect_id'] ?>">
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
    <h2><?= $form ? 'Edit suspect' : 'Add suspect' ?></h2>
    <input type="hidden" name="action" value="<?= $form ? 'update' : 'create' ?>">
    <?php if ($form): ?><input type="hidden" name="suspect_id" value="<?= (int) $form['suspect_id'] ?>"><?php endif; ?>
    <div class="stack">
        <label>Name <input name="name" required value="<?= h($form['name'] ?? '') ?>"></label>
        <div class="grid-2">
            <label>Age <input type="number" name="age" min="10" max="100" required value="<?= h((string) ($form['age'] ?? '25')) ?>"></label>
            <label>Gender
                <select name="gender">
                    <?php foreach (['Male', 'Female', 'Other'] as $g): ?><option value="<?= h($g) ?>" <?= selected($form['gender'] ?? 'Male', $g) ?>><?= h($g) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Criminal record <input name="criminal_record" value="<?= h($form['criminal_record'] ?? '') ?>"></label>
        <label>Linked victim
            <select name="victim_id">
                <option value="">None</option>
                <?php foreach ($victims as $item): ?><option value="<?= h((string) $item['victim_id']) ?>" <?= selected($form['victim_id'] ?? '', $item['victim_id']) ?>><?= h($item['name']) ?></option><?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="modal-actions"><button class="btn" type="button" data-close-modal>Cancel</button><button class="btn primary">Save</button></div>
</form>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
