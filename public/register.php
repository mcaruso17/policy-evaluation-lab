<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

if (current_user()) {
    redirect('index.php');
}

$errors = [];
$v = [
    'first_name' => '', 'last_name' => '', 'email' => '',
    'matricola'  => '', 'programme' => '', 'attending' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && registration_open()) {
    check_csrf();
    foreach ($v as $k => $_) {
        $v[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $v['email'] = strtolower($v['email']);
    $password   = (string) ($_POST['password'] ?? '');
    $password2  = (string) ($_POST['password2'] ?? '');
    $code       = trim((string) ($_POST['access_code'] ?? ''));

    if (too_many_attempts()) {
        $errors[] = 'Too many attempts. Wait 15 minutes and try again.';
    } else {
        if (!hash_equals(access_code(), $code)) {
            $errors[] = 'The course access code is not correct.';
            record_failed_attempt();
        }
        if ($v['first_name'] === '' || $v['last_name'] === '') {
            $errors[] = 'Enter your first and last name.';
        }
        if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid e-mail address.';
        } else {
            $domains = config('allowed_email_domains') ?? [];
            $domain  = substr(strrchr($v['email'], '@'), 1);
            $isAdmin = in_array($v['email'], array_map('strtolower', config('admin_emails') ?? []), true);
            if ($domains && !$isAdmin && !in_array($domain, $domains, true)) {
                $errors[] = 'Use your university e-mail (' . implode(' or ', array_map(fn($d) => '@' . $d, $domains)) . ').';
            }
        }
        if ($v['matricola'] === '') {
            $errors[] = 'Enter your student ID (matricola).';
        }
        if ($v['programme'] === '') {
            $errors[] = 'Enter your degree programme.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'The password must be at least 8 characters.';
        } elseif ($password !== $password2) {
            $errors[] = 'The two passwords do not match.';
        }
        if (empty($_POST['privacy'])) {
            $errors[] = 'Please accept the privacy notice.';
        }
    }

    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$v['email']]);
        if ($st->fetchColumn()) {
            $errors[] = 'This e-mail is already registered. Log in instead.';
        }
    }

    if (!$errors) {
        db()->prepare(
            'INSERT INTO users (first_name, last_name, email, matricola, programme, attending, password_hash, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $v['first_name'], $v['last_name'], $v['email'], $v['matricola'], $v['programme'],
            $v['attending'] === '1' ? 1 : 0,
            password_hash($password, PASSWORD_DEFAULT), now(),
        ]);
        $st = db()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$v['email']]);
        log_in($st->fetch());
        flash('Welcome, ' . $v['first_name'] . '! Your account is ready.');
        redirect('index.php');
    }
}

page_header('Register', 'register.php');
page_head('Register', 'Create your account for the course website. You need the access code given in class.');
?>

<?php if (!registration_open()): ?>
      <div class="callout callout-warn"><p>Registration is closed. If you need access, write to the lecturer.</p></div>
<?php else: ?>

<?php if ($errors): ?>
      <div class="callout callout-danger">
<?php foreach ($errors as $err): ?>
        <p><?= e($err) ?></p>
<?php endforeach; ?>
      </div>
<?php endif; ?>

      <form method="post" class="pel-form" autocomplete="on">
        <?= csrf_field() ?>
        <div class="pel-row">
          <label>First name
            <input type="text" name="first_name" value="<?= e($v['first_name']) ?>" required autocomplete="given-name">
          </label>
          <label>Last name
            <input type="text" name="last_name" value="<?= e($v['last_name']) ?>" required autocomplete="family-name">
          </label>
        </div>
        <label>University e-mail
          <input type="email" name="email" value="<?= e($v['email']) ?>" required autocomplete="email"
                 placeholder="name.surname@stud.uniroma3.it">
        </label>
        <div class="pel-row">
          <label>Student ID (matricola)
            <input type="text" name="matricola" value="<?= e($v['matricola']) ?>" required>
          </label>
          <label>Degree programme
            <input type="text" name="programme" value="<?= e($v['programme']) ?>" required
                   placeholder="Master's degree name, or Erasmus">
          </label>
        </div>
        <fieldset class="pel-radio">
          <legend>Will you attend the lectures?</legend>
          <label><input type="radio" name="attending" value="1" <?= $v['attending'] === '1' ? 'checked' : '' ?>> Yes, attending</label>
          <label><input type="radio" name="attending" value="0" <?= $v['attending'] === '0' ? 'checked' : '' ?>> No, non-attending</label>
        </fieldset>
        <div class="pel-row">
          <label>Password (min. 8 characters)
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
          </label>
          <label>Repeat password
            <input type="password" name="password2" required minlength="8" autocomplete="new-password">
          </label>
        </div>
        <label>Course access code
          <input type="text" name="access_code" required autocomplete="off">
        </label>
        <label class="pel-check">
          <input type="checkbox" name="privacy" value="1" required>
          I have read the <a href="privacy.php" target="_blank">privacy notice</a>.
        </label>
        <button type="submit" class="btn btn-primary">Create account</button>
        <p class="pel-muted">Already registered? <a href="login.php">Log in</a>.</p>
      </form>
<?php endif; ?>

<?php page_footer();
