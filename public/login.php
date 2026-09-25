<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

if (current_user()) {
    redirect('index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (too_many_attempts()) {
        $error = 'Too many failed attempts. Wait 15 minutes and try again.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                $error = 'This account has been disabled. Write to the lecturer.';
            } else {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                }
                log_in($user);
                $to = $_SESSION['after_login'] ?? 'index.php';
                unset($_SESSION['after_login']);
                // only ever bounce back to a page on this site
                redirect(preg_match('~^/[^/\\\\]~', $to) ? $to : 'index.php');
            }
        } else {
            record_failed_attempt();
            $error = 'E-mail or password not correct.';
        }
    }
}

$c = course()['course'];
page_header('Log in', 'login.php');
?>
      <div class="gate-overlay">
        <div class="gate-card">
          <span class="page-mascot" data-bear="teaching"></span>
          <h2><?= e($c['title']) ?></h2>
          <p><?= e($c['university']) ?> · <?= e($c['year']) ?><br>Course materials for registered students.</p>
<?php if ($error): ?>
          <p class="gate-error"><?= e($error) ?></p>
<?php endif; ?>
          <form method="post" class="pel-form pel-form-compact">
            <?= csrf_field() ?>
            <label>E-mail
              <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="username" autofocus>
            </label>
            <label>Password
              <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit" class="btn btn-primary">Log in</button>
          </form>
          <p class="pel-muted">
            First time here? <a href="register.php">Register</a> with your university e-mail.<br>
            Forgot your password? Write to <?= e($c['email']) ?> and it will be reset.
          </p>
        </div>
      </div>
<?php page_footer();
