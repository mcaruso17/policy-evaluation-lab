<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

$me = require_admin();

/* ── CSV export ────────────────────────────────────────────────────────────── */
if (($_GET['export'] ?? '') === 'csv') {
    $rows = db()->query('SELECT last_name, first_name, email, matricola, programme, attending,
                                created_at, last_login_at, login_count, is_active
                         FROM users ORDER BY last_name COLLATE NOCASE, first_name')->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pel-students-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads the accents
    fputcsv($out, array_keys($rows[0] ?? [
        'last_name' => 0, 'first_name' => 0, 'email' => 0, 'matricola' => 0, 'programme' => 0,
        'attending' => 0, 'created_at' => 0, 'last_login_at' => 0, 'login_count' => 0, 'is_active' => 0,
    ]), ';', '"', '');
    foreach ($rows as $r) {
        fputcsv($out, $r, ';', '"', '');
    }
    exit;
}

/* ── Actions ───────────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'settings') {
        $code = trim((string) ($_POST['access_code'] ?? ''));
        if ($code !== '') {
            set_setting('access_code', $code);
        }
        set_setting('registration_open', !empty($_POST['registration_open']) ? '1' : '0');
        flash('Settings saved.');
    } elseif ($id && $id !== (int) $me['id']) {
        if ($action === 'toggle') {
            db()->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            flash('Account updated.');
        } elseif ($action === 'delete') {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('Account deleted.');
        } elseif ($action === 'reset') {
            // readable temporary password: 3 groups of 4 characters
            $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
            $tmp = '';
            for ($i = 0; $i < 12; $i++) {
                $tmp .= $alphabet[random_int(0, strlen($alphabet) - 1)] . (($i % 4 === 3 && $i < 11) ? '-' : '');
            }
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($tmp, PASSWORD_DEFAULT), $id]);
            $st = db()->prepare('SELECT email FROM users WHERE id = ?');
            $st->execute([$id]);
            flash('Temporary password for ' . $st->fetchColumn() . ': ' . $tmp . ' — send it to the student; they can change it under Account.');
        }
    }
    redirect('admin.php');
}

/* ── Data ──────────────────────────────────────────────────────────────────── */
$users    = db()->query('SELECT * FROM users ORDER BY last_name COLLATE NOCASE, first_name')->fetchAll();
$students = array_filter($users, fn($u) => !is_admin($u)); // stats count students only
$total     = count($students);
$attending = count(array_filter($students, fn($u) => (int) $u['attending'] === 1));
$active7   = count(array_filter($students, fn($u) => $u['last_login_at'] && strtotime($u['last_login_at']) > time() - 7 * 86400));
$byProg    = [];
foreach ($students as $u) {
    $byProg[$u['programme']] = ($byProg[$u['programme']] ?? 0) + 1;
}
arsort($byProg);

page_header('Students', 'admin.php');
page_head('Students', 'Registered students, access code and registration settings. Only visible to you.');
?>

      <div class="pel-stats">
        <div><span class="pel-stat-num"><?= $total ?></span><span class="pel-stat-label">registered</span></div>
        <div><span class="pel-stat-num"><?= $attending ?></span><span class="pel-stat-label">attending</span></div>
        <div><span class="pel-stat-num"><?= $total - $attending ?></span><span class="pel-stat-label">non-attending</span></div>
        <div><span class="pel-stat-num"><?= $active7 ?></span><span class="pel-stat-label">active last 7 days</span></div>
      </div>

<?php if ($byProg): ?>
      <p class="pel-muted">
<?php foreach ($byProg as $p => $n): ?>
        <?= e($p) ?>: <strong><?= $n ?></strong> &nbsp;
<?php endforeach; ?>
      </p>
<?php endif; ?>

      <p class="section-label">Settings</p>
      <form method="post" class="pel-form pel-form-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="settings">
        <label>Access code
          <input type="text" name="access_code" value="<?= e(access_code()) ?>" autocomplete="off">
        </label>
        <label class="pel-check">
          <input type="checkbox" name="registration_open" value="1" <?= registration_open() ? 'checked' : '' ?>>
          Registration open
        </label>
        <button type="submit" class="btn btn-secondary">Save</button>
      </form>

      <p class="section-label">Registered students
        <a class="paper-link pel-right" href="admin.php?export=csv">Download CSV</a>
      </p>
      <div class="pel-table-wrap">
        <table class="comparison-table pel-table">
          <thead>
            <tr>
              <th>Name</th><th>E-mail</th><th>Matricola</th><th>Programme</th>
              <th>Att.</th><th>Registered</th><th>Last login</th><th></th>
            </tr>
          </thead>
          <tbody>
<?php foreach ($users as $u): ?>
            <tr class="<?= $u['is_active'] ? '' : 'pel-disabled' ?>">
              <td><?= e($u['last_name']) ?>, <?= e($u['first_name']) ?><?= is_admin($u) ? ' <span class="pel-sched">admin</span>' : '' ?></td>
              <td><a href="mailto:<?= e($u['email']) ?>"><?= e($u['email']) ?></a></td>
              <td><?= e($u['matricola']) ?></td>
              <td><?= e($u['programme']) ?></td>
              <td><?= $u['attending'] ? 'Yes' : 'No' ?></td>
              <td><?= e(substr($u['created_at'], 0, 10)) ?></td>
              <td><?= e($u['last_login_at'] ? substr($u['last_login_at'], 0, 10) : '—') ?></td>
              <td class="pel-actions">
<?php if ((int) $u['id'] !== (int) $me['id']): ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button name="action" value="reset" title="Generate a temporary password">Reset pw</button>
                  <button name="action" value="toggle"><?= $u['is_active'] ? 'Disable' : 'Enable' ?></button>
                  <button name="action" value="delete" class="pel-danger"
                          onclick="return confirm('Delete this account permanently?')">Delete</button>
                </form>
<?php endif; ?>
              </td>
            </tr>
<?php endforeach; ?>
<?php if (!$users): ?>
            <tr><td colspan="8" class="pel-muted">Nobody has registered yet.</td></tr>
<?php endif; ?>
          </tbody>
        </table>
      </div>

<?php page_footer();
