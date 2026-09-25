<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

$user   = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'password') {
        $current = (string) ($_POST['current'] ?? '');
        $new     = (string) ($_POST['new'] ?? '');
        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Your current password is not correct.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'The new password must be at least 8 characters.';
        } elseif ($new !== ($_POST['new2'] ?? '')) {
            $errors[] = 'The two new passwords do not match.';
        } else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('Password changed.');
            redirect('account.php');
        }
    } elseif ($action === 'attending') {
        db()->prepare('UPDATE users SET attending = ? WHERE id = ?')
            ->execute([($_POST['attending'] ?? '1') === '1' ? 1 : 0, $user['id']]);
        flash('Saved.');
        redirect('account.php');
    } elseif ($action === 'delete' && !is_admin($user)) {
        if (password_verify((string) ($_POST['confirm_password'] ?? ''), $user['password_hash'])) {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]);
            $_SESSION = [];
            session_destroy();
            redirect('login.php');
        }
        $errors[] = 'Password not correct: account not deleted.';
    }
}

page_header('Account', 'account.php');
page_head('Your account', e($user['first_name'] . ' ' . $user['last_name']) . ' · ' . e($user['email']) . ' · matricola ' . e($user['matricola']));
?>

<?php if ($errors): ?>
      <div class="callout callout-danger">
<?php foreach ($errors as $err): ?>
        <p><?= e($err) ?></p>
<?php endforeach; ?>
      </div>
<?php endif; ?>

      <p class="section-label">Attendance</p>
      <form method="post" class="pel-form pel-form-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="attending">
        <label class="pel-check"><input type="radio" name="attending" value="1" <?= $user['attending'] ? 'checked' : '' ?>> Attending</label>
        <label class="pel-check"><input type="radio" name="attending" value="0" <?= $user['attending'] ? '' : 'checked' ?>> Non-attending</label>
        <button type="submit" class="btn btn-secondary">Save</button>
      </form>

      <p class="section-label">Change password</p>
      <form method="post" class="pel-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password">
        <label>Current password
          <input type="password" name="current" required autocomplete="current-password">
        </label>
        <div class="pel-row">
          <label>New password
            <input type="password" name="new" required minlength="8" autocomplete="new-password">
          </label>
          <label>Repeat new password
            <input type="password" name="new2" required minlength="8" autocomplete="new-password">
          </label>
        </div>
        <button type="submit" class="btn btn-primary">Change password</button>
      </form>

<?php if (!is_admin($user)): ?>
      <p class="section-label">Delete account</p>
      <form method="post" class="pel-form pel-form-inline"
            onsubmit="return confirm('Delete your account and all your data from this site?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <label>Confirm with your password
          <input type="password" name="confirm_password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-secondary pel-danger">Delete my account</button>
      </form>
<?php endif; ?>

<?php page_footer();
