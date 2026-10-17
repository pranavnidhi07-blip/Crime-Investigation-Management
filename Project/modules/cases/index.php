<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/helpers.php';
require_login();

$title = 'Case Management';
$active = 'cases';
$q = trim((string) get('q'));
$status = (string) get('status');
$from = (string) get('from');
$to = (string) get('to');
$viewId = (int) get('id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) post('action');
    try {
        if ($action === 'create' || $action === 'update') {
            $currentUserId = current_user_id();
            $primaryOfficer = post('officer_id') !== '' ? (int) post('officer_id') : $currentUserId;
            if ($primaryOfficer <= 0) {
                $primaryOfficer = $currentUserId;
            }
            $params = [
                trim((string) post('title')),
                trim((string) post('description')),
                (string) post('status'),
                (string) post('date_opened'),
                $primaryOfficer,
                post('team_id') !== '' ? (int) post('team_id') : null,
                post('location_id') !== '' ? (int) post('location_id') : null,
            ];
            if ($action === 'create') {
                exec_stmt(
                    'INSERT INTO `case` (title, description, status, date_opened, officer_id, team_id, location_id)
                     VALUES (?,?,?,?,?,?,?)',
                    $params
                );
                $caseId = (int) db()->lastInsertId();
                flash('Case created.');
            } else {
                $caseId = (int) post('case_id');
                exec_stmt(
                    'UPDATE `case` SET title=?, description=?, status=?, date_opened=?, officer_id=?, team_id=?, location_id=? WHERE case_id=?',
                    [...$params, $caseId]
                );
                flash('Case updated.');
            }
            exec_stmt('DELETE FROM case_suspect WHERE case_id=?', [$caseId]);
            foreach ((array) post('suspect_ids', []) as $sid) {
                exec_stmt('INSERT INTO case_suspect (case_id, suspect_id) VALUES (?,?)', [$caseId, (int) $sid]);
            }
            exec_stmt('DELETE FROM case_officer WHERE case_id=?', [$caseId]);
            $assignedOfficers = array_values(array_unique(array_filter(
                [(int) $primaryOfficer, $currentUserId],
                static fn (int $officerId): bool => $officerId > 0
            )));
            foreach ((array) post('extra_officer_ids', []) as $oid) {
                $oidInt = (int) $oid;
                if ($oidInt > 0 && !in_array($oidInt, $assignedOfficers, true)) {
                    $assignedOfficers[] = $oidInt;
                }
            }
            foreach ($assignedOfficers as $oid) {
                exec_stmt('INSERT INTO case_officer (case_id, officer_id) VALUES (?,?)', [$caseId, $oid]);
            }
        } elseif ($action === 'delete') {
            exec_stmt('DELETE FROM `case` WHERE case_id=?', [(int) post('case_id')]);
            flash('Case deleted.');
        }
    } catch (Throwable $e) {
        safe_error($e);
    }
    redirect(base_url('modules/cases/index.php'));
}

$userId = current_user_id();
$caseAccess = case_access_clause('c');
$caseParams = case_access_params($userId);

$sql = 'SELECT c.*, o.name AS officer_name, t.team_name, l.city
        FROM `case` c
        LEFT JOIN officer o ON o.officer_id = c.officer_id
        LEFT JOIN investigation_team t ON t.team_id = c.team_id
        LEFT JOIN location l ON l.location_id = c.location_id
        WHERE ' . $caseAccess;
$params = $caseParams;
if ($q !== '') {
    $sql .= ' AND (LOWER(c.title) LIKE LOWER(?) OR CAST(c.case_id AS CHAR) LIKE ?)';
    $params[] = pager_like($q);
    $params[] = pager_like($q);
}
if ($status !== '') {
    $sql .= ' AND c.status = ?';
    $params[] = $status;
}
if ($from !== '') {
    $sql .= ' AND c.date_opened >= ?';
    $params[] = $from;
}
if ($to !== '') {
    $sql .= ' AND c.date_opened <= ?';
    $params[] = $to;
}
$sql .= ' ORDER BY c.date_opened DESC, c.case_id DESC';
$rows = fetch_all($sql, $params);

$officers = options('SELECT officer_id, name FROM officer ORDER BY name', 'officer_id', 'name');
$teams = options('SELECT team_id, team_name FROM investigation_team ORDER BY team_name', 'team_id', 'team_name');
$locations = options("SELECT location_id, CONCAT(city, ' — ', address) AS label FROM location ORDER BY city", 'location_id', 'label');
$suspects = fetch_all(
    'SELECT s.suspect_id, s.name
     FROM suspect s
     ORDER BY s.name'
);

$detail = null;
$linkedSuspects = [];
$linkedOfficers = [];
if ($viewId) {
    $detail = fetch_one(
        'SELECT c.*, o.name AS officer_name, t.team_name, l.address, l.city, l.state
         FROM `case` c
         LEFT JOIN officer o ON o.officer_id = c.officer_id
         LEFT JOIN investigation_team t ON t.team_id = c.team_id
         LEFT JOIN location l ON l.location_id = c.location_id
         WHERE c.case_id = ? AND ' . $caseAccess,
        array_merge([$viewId], $caseParams)
    );
    $linkedSuspects = fetch_all(
        'SELECT s.suspect_id, s.name FROM case_suspect cs JOIN suspect s ON s.suspect_id = cs.suspect_id WHERE cs.case_id = ?',
        [$viewId]
    );
    $linkedOfficers = fetch_all(
        'SELECT o.officer_id, o.name FROM case_officer co JOIN officer o ON o.officer_id = co.officer_id WHERE co.case_id = ?',
        [$viewId]
    );
}

$editing = get('open') === '1';
$edit = $editing ? $detail : null;
$editSuspects = ($editing && $viewId) ? array_column($linkedSuspects, 'suspect_id') : [];
$editOfficers = ($editing && $viewId) ? array_column($linkedOfficers, 'officer_id') : [];

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="toolbar">
    <form method="get">
        <input name="q" placeholder="Search ID or title" value="<?= h($q) ?>">
        <select name="status">
            <option value="">All statuses</option>
            <?php foreach (['Open', 'Under Investigation', 'Closed'] as $opt): ?>
                <option value="<?= h($opt) ?>" <?= selected($status, $opt) ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?= h($from) ?>">
        <input type="date" name="to" value="<?= h($to) ?>">
        <button class="btn" type="submit">Filter</button>
    </form>
    <button class="btn primary" type="button" data-open-modal="caseModal">Add case</button>
</div>

<?php if ($detail): ?>
<section class="panel">
    <h2>Case #<?= (int) $detail['case_id'] ?> — <?= h($detail['title']) ?></h2>
    <p><?= h($detail['description']) ?></p>
    <p>Status: <span class="badge <?= h(strtolower(str_replace(' ', '-', $detail['status']))) ?>"><?= h($detail['status']) ?></span>
        · Opened <?= h($detail['date_opened']) ?></p>
    <p>Primary investigator: <?= h($detail['officer_name'] ?? 'Unassigned') ?></p>
    <p>Team: <?= h($detail['team_name'] ?? 'Unassigned') ?></p>
    <p>Location: <?= h(trim(($detail['address'] ?? '') . ', ' . ($detail['city'] ?? '') . ' ' . ($detail['state'] ?? ''))) ?></p>
    <p>Suspects: <?= $linkedSuspects ? h(implode(', ', array_column($linkedSuspects, 'name'))) : 'None linked' ?></p>
    <p>Additional officers: <?= $linkedOfficers ? h(implode(', ', array_column($linkedOfficers, 'name'))) : 'None' ?></p>
    <a class="btn" href="?id=<?= (int) $detail['case_id'] ?>&open=1">Edit this case</a>
</section>
<?php endif; ?>

<section class="panel">
    <?php if (!$rows): ?>
        <p class="empty">No cases match the current filters.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Opened</th><th>Officer</th><th>City</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= (int) $row['case_id'] ?></td>
                        <td><?= h($row['title']) ?></td>
                        <td><span class="badge <?= h(strtolower(str_replace(' ', '-', $row['status']))) ?>"><?= h($row['status']) ?></span></td>
                        <td><?= h($row['date_opened']) ?></td>
                        <td><?= h($row['officer_name'] ?? '—') ?></td>
                        <td><?= h($row['city'] ?? '—') ?></td>
                        <td class="row-actions">
                            <a class="btn small" href="?id=<?= (int) $row['case_id'] ?>">Details</a>
                            <form method="post" data-confirm="Delete this case? Evidence rows must be removed first if they exist.">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="case_id" value="<?= (int) $row['case_id'] ?>">
                                <button class="btn small danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<div class="modal" id="caseModal">
    <form class="modal-card" method="post" data-loading>
        <h2><?= $edit ? 'Edit case' : 'Add case' ?></h2>
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
        <?php if ($edit): ?><input type="hidden" name="case_id" value="<?= (int) $edit['case_id'] ?>"><?php endif; ?>
        <div class="stack">
            <label>Title <input name="title" required value="<?= h($edit['title'] ?? '') ?>"></label>
            <label>Description <textarea name="description" required><?= h($edit['description'] ?? '') ?></textarea></label>
            <div class="grid-2">
                <label>Status
                    <select name="status" required>
                        <?php foreach (['Open', 'Under Investigation', 'Closed'] as $opt): ?>
                            <option value="<?= h($opt) ?>" <?= selected($edit['status'] ?? 'Open', $opt) ?>><?= h($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Date opened <input type="date" name="date_opened" required value="<?= h($edit['date_opened'] ?? date('Y-m-d')) ?>"></label>
            </div>
            <div class="grid-2">
                <label>Primary officer
                    <select name="officer_id">
                        <option value="">Unassigned</option>
                        <?php foreach ($officers as $id => $name): ?>
                            <option value="<?= h($id) ?>" <?= selected($edit['officer_id'] ?? '', $id) ?>><?= h($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Team
                    <select name="team_id">
                        <option value="">Unassigned</option>
                        <?php foreach ($teams as $id => $name): ?>
                            <option value="<?= h($id) ?>" <?= selected($edit['team_id'] ?? '', $id) ?>><?= h($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label>Location
                <select name="location_id">
                    <option value="">Select location</option>
                    <?php foreach ($locations as $id => $name): ?>
                        <option value="<?= h($id) ?>" <?= selected($edit['location_id'] ?? '', $id) ?>><?= h($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Involved suspects
                <select name="suspect_ids[]" multiple size="4">
                    <?php foreach ($suspects as $s): ?>
                        <option value="<?= (int) $s['suspect_id'] ?>" <?= in_array((int) $s['suspect_id'], array_map('intval', $editSuspects), true) ? 'selected' : '' ?>><?= h($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Additional officers
                <select name="extra_officer_ids[]" multiple size="4">
                    <?php foreach ($officers as $id => $name): ?>
                        <option value="<?= h($id) ?>" <?= in_array((int) $id, array_map('intval', $editOfficers), true) ? 'selected' : '' ?>><?= h($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="modal-actions">
            <button class="btn" type="button" data-close-modal>Cancel</button>
            <button class="btn primary" type="submit">Save</button>
        </div>
    </form>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
