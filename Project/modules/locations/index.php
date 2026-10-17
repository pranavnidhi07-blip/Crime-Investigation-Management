<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_login();

$title = 'Location Management';
$active = 'locations';
$q = trim((string) get('q'));
$city = (string) get('city');
$viewId = (int) get('id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) post('action');
    try {
        $fields = [
            trim((string) post('address')),
            trim((string) post('city')),
            trim((string) post('state')),
            trim((string) post('description')),
        ];
        if ($action === 'create') {
            exec_stmt('INSERT INTO location (address, city, state, description) VALUES (?,?,?,?)', $fields);
            flash('Location added.');
        } elseif ($action === 'update') {
            exec_stmt('UPDATE location SET address=?, city=?, state=?, description=? WHERE location_id=?', [...$fields, (int) post('location_id')]);
            flash('Location updated.');
        } elseif ($action === 'delete') {
            exec_stmt('DELETE FROM location WHERE location_id=?', [(int) post('location_id')]);
            flash('Location deleted.');
        }
    } catch (Throwable $e) {
        safe_error($e);
    }
    redirect(base_url('modules/locations/index.php'));
}

$sql = 'SELECT * FROM location WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (LOWER(address) LIKE LOWER(?) OR LOWER(city) LIKE LOWER(?) OR LOWER(state) LIKE LOWER(?))';
    $params[] = pager_like($q);
    $params[] = pager_like($q);
    $params[] = pager_like($q);
}
if ($city !== '') {
    $sql .= ' AND LOWER(city) = LOWER(?)';
    $params[] = $city;
}
$sql .= ' ORDER BY city, address';
$rows = fetch_all($sql, $params);
$cities = array_column(fetch_all('SELECT DISTINCT city FROM location ORDER BY city'), 'city');
$detail = $viewId ? fetch_one('SELECT * FROM location WHERE location_id=?', [$viewId]) : null;
$form = get('open') === '1' ? $detail : null;
$cases = $viewId ? fetch_all('SELECT case_id, title, status FROM `case` WHERE location_id=?', [$viewId]) : [];
$evidence = $viewId ? fetch_all('SELECT evidence_id, type, status FROM evidence WHERE location_id=?', [$viewId]) : [];

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get">
        <input name="q" placeholder="Search address or city" value="<?= h($q) ?>">
        <select name="city">
            <option value="">All cities</option>
            <?php foreach ($cities as $c): ?><option value="<?= h($c) ?>" <?= selected($city, $c) ?>><?= h($c) ?></option><?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Filter</button>
    </form>
    <button class="btn primary" data-open-modal="itemModal" type="button">Add location</button>
</div>
<?php if ($detail): ?>
<section class="panel">
    <h2><?= h($detail['city']) ?>, <?= h($detail['state']) ?></h2>
    <p><?= h($detail['address']) ?></p>
    <p><?= h($detail['description']) ?></p>
    <h3>Cases here</h3>
    <?php if (!$cases): ?><p class="empty">No cases at this location.</p>
    <?php else: ?><ul><?php foreach ($cases as $c): ?><li>#<?= (int) $c['case_id'] ?> <?= h($c['title']) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <h3>Evidence found here</h3>
    <?php if (!$evidence): ?><p class="empty">No evidence logged.</p>
    <?php else: ?><ul><?php foreach ($evidence as $e): ?><li>#<?= (int) $e['evidence_id'] ?> <?= h($e['type']) ?> (<?= h($e['status']) ?>)</li><?php endforeach; ?></ul><?php endif; ?>
</section>
<?php endif; ?>
<section class="panel">
<?php if (!$rows): ?><p class="empty">No locations found.</p>
<?php else: ?>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Address</th><th>City</th><th>State</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><?= (int) $row['location_id'] ?></td>
    <td><?= h($row['address']) ?></td>
    <td><?= h($row['city']) ?></td>
    <td><?= h($row['state']) ?></td>
    <td class="row-actions">
        <a class="btn small" href="?id=<?= (int) $row['location_id'] ?>">Related</a>
        <a class="btn small" href="?id=<?= (int) $row['location_id'] ?>&open=1">Edit</a>
        <form method="post" data-confirm="Delete this location? It will fail if cases or evidence still reference it.">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="location_id" value="<?= (int) $row['location_id'] ?>">
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
    <h2><?= $form ? 'Edit location' : 'Add location' ?></h2>
    <input type="hidden" name="action" value="<?= $form ? 'update' : 'create' ?>">
    <?php if ($form): ?><input type="hidden" name="location_id" value="<?= (int) $form['location_id'] ?>"><?php endif; ?>
    <div class="stack">
        <label>Address <input name="address" required value="<?= h($form['address'] ?? '') ?>"></label>
        <div class="grid-2">
            <label>City <input name="city" required value="<?= h($form['city'] ?? '') ?>"></label>
            <label>State <input name="state" required value="<?= h($form['state'] ?? '') ?>"></label>
        </div>
        <label>Description <input name="description" value="<?= h($form['description'] ?? '') ?>"></label>
    </div>
    <div class="modal-actions"><button class="btn" type="button" data-close-modal>Cancel</button><button class="btn primary">Save</button></div>
</form>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
