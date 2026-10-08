<?php
/* ─── Page chrome — same nav, footer and theme toggle as carusomatteo.it ───── */

function page_header(string $title, string $active = ''): void
{
    $user = current_user();
    $c    = course()['course'];

    if ($user) {
        $links = [
            'index.php'     => 'Home',
            'materials.php' => 'Lectures',
            'exercises.php' => 'Exercises',
            'exam.php'      => 'Exam',
            'resources.php' => 'Resources',
        ];
        if (is_admin($user)) {
            $links['admin.php'] = 'Students';
        }
        $links['account.php'] = 'Account';
    } else {
        $links = [
            'login.php'    => 'Log in',
            'register.php' => 'Register',
        ];
    }
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> — <?= e($c['title']) ?></title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="assets/pel.css">
</head>
<body>

  <nav>
    <a class="nav-logo" href="index.php"><?= e($c['title']) ?></a>
    <ul class="nav-links">
<?php foreach ($links as $href => $label): ?>
      <li><a href="<?= e($href) ?>"<?= $href === $active ? ' class="active"' : '' ?>><?= e($label) ?></a></li>
<?php endforeach; ?>
      <li><a href="https://carusomatteo.it" target="_blank" rel="noopener">carusomatteo.it ↗</a></li>
    </ul>
    <button id="dark-toggle" aria-label="Toggle dark mode">Dark</button>
  </nav>

  <main>
    <div class="page-wrap">
<?php if ($m = flash()): ?>
      <div class="callout callout-info pel-flash"><p><?= e($m) ?></p></div>
<?php endif;
}

function page_footer(): void
{
    $c = course()['course'];
    ?>
    </div>
  </main>

  <footer>
    <span><?= e($c['title']) ?> · <?= e($c['university']) ?> · <?= e($c['year']) ?></span>
    <span>·</span>
    <a href="privacy.php">Privacy</a>
<?php if (current_user()): ?>
    <span>·</span>
    <a href="logout.php">Log out</a>
<?php endif; ?>
  </footer>

  <script src="assets/js/bear.js"></script>
  <script src="assets/js/main.js"></script>

</body>
</html>
<?php
}

/* The page title block used on every page: serif title, muted lead, bear. */
function page_head(string $title, string $lead = '', string $bear = 'plain'): void
{
    ?>
      <div class="page-head">
        <div>
          <h1 class="page-title"><?= e($title) ?></h1>
<?php if ($lead !== ''): ?>
          <p class="page-lead"><?= $lead ?></p>
<?php endif; ?>
        </div>
        <span class="page-mascot" data-bear="<?= e($bear) ?>"></span>
      </div>
<?php
}
