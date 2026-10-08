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

/* The page title block used on every page: large serif title on the left,
   the lead paragraph on the right, the bear at the end. */
function page_head(string $title, string $lead = '', string $bear = 'plain'): void
{
    ?>
      <div class="page-head pel-hero">
        <h1 class="page-title"><?= e($title) ?></h1>
        <div class="pel-hero-side">
<?php if ($lead !== ''): ?>
          <p class="page-lead"><?= $lead ?></p>
<?php endif; ?>
        </div>
        <span class="page-mascot" data-bear="<?= e($bear) ?>" data-bear-size="68"></span>
      </div>
<?php
}

/* Section heading (replaces the small uppercase label on redesigned pages). */
function section_title(string $title, string $extra = ''): void
{
    echo '      <h2 class="pel-section">' . e($title) . $extra . "</h2>\n";
}

/* Course items as a list of rows: label on the left, type on the right,
   hairline between rows. Same visibility rules as item_link(). */
function item_rows(array $items): string
{
    $admin = is_admin();
    $out   = '';
    foreach ($items as $item) {
        if (!item_available($item) && !$admin) {
            continue;
        }
        $href  = !empty($item['file']) ? 'file.php?f=' . rawurlencode($item['file']) : ($item['url'] ?? '#');
        $type  = ITEM_LABELS[$item['type'] ?? ''] ?? '';
        $sched = !item_available($item) ? ' <span class="pel-sched">from ' . e($item['available_from']) . '</span>' : '';
        $out  .= '<li><a href="' . e($href) . '" target="_blank" rel="noopener">'
               . '<span class="pel-row-label">' . e($item['label'] ?? '') . $sched . '</span>'
               . '<span class="pel-row-type">' . e($type) . ' ↗</span></a></li>' . "\n";
    }
    return $out === '' ? '' : '<ul class="pel-rows">' . "\n" . $out . '</ul>';
}

/* The week of the course we are in (1 … number of weeks), from course.start_date;
   null before the start or after the end. */
function current_week(): ?int
{
    $start = course()['course']['start_date'] ?? null;
    if (!$start) {
        return null;
    }
    $days = (int) floor((strtotime(date('Y-m-d')) - strtotime($start)) / 86400);
    $week = intdiv($days, 7) + 1;
    return ($days >= 0 && $week <= count(course()['weeks'])) ? $week : null;
}
