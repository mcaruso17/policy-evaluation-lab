<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

$user = require_login();
$data = course();
$c    = $data['course'];

page_header('Home', 'index.php');
page_head($c['title'], e($c['university']) . ' — ' . e($c['department']) . ' · ' . e($c['year']) . '<br>' . e($c['details']));
?>

<?php
$cw    = current_week();
$focus = null;
foreach ($data['weeks'] as $w) {
    if ((int) $w['week'] === ($cw ?? 1)) {
        $focus = $w;
    }
}
?>
<?php if ($focus): ?>
      <section class="pel-panel">
        <div class="pel-panel-text">
          <p class="pel-kicker"><?= $cw ? 'This week' : 'Coming up' ?> · Week <?= (int) $focus['week'] ?><?= !empty($focus['dates']) ? ' · ' . e($focus['dates']) : '' ?></p>
          <h2 class="pel-panel-title"><?= e($focus['topic']) ?></h2>
<?php if (!empty($focus['summary'])): ?>
          <p class="pel-panel-lead"><?= $focus['summary'] ?></p>
<?php endif; ?>
          <a class="pel-button" href="materials.php#week-<?= (int) $focus['week'] ?>">All lectures →</a>
        </div>
        <div class="pel-panel-items">
          <?= item_rows($focus['items'] ?? []) ?: '<p class="pel-muted">Material for this week will appear here.</p>' ?>
        </div>
      </section>
<?php endif; ?>

<?php section_title('Announcements'); ?>
<?php if (empty($data['announcements'])): ?>
      <p class="pel-muted pel-pad">No announcements yet.</p>
<?php else: ?>
      <div class="pel-cards">
<?php foreach ($data['announcements'] as $a): ?>
        <article class="pel-card">
          <p class="pel-card-title"><?= e($a['title']) ?></p>
          <p class="pel-card-text"><?= $a['body'] /* trusted HTML from course.json */ ?></p>
          <dl class="pel-meta">
            <div><dt>Date</dt><dd><?= e(date('j F Y', strtotime($a['date']))) ?></dd></div>
          </dl>
        </article>
<?php endforeach; ?>
      </div>
<?php endif; ?>

      <section class="pel-split">
        <div>
          <h2 class="pel-split-title">A hands-on course on how to tell whether a policy worked.</h2>
          <div class="pel-split-links">
            <a class="pel-button" href="materials.php">Lectures →</a>
            <a class="pel-button pel-button-ghost" href="resources.php">Resources</a>
          </div>
        </div>
        <dl class="pel-list">
          <div><dt>Lecturer</dt><dd><?= e($c['lecturer']) ?> · <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></dd></div>
          <div><dt>Timetable</dt><dd><?= e($c['timetable']) ?></dd></div>
<?php if (!empty($c['location'])): ?>
          <div><dt>Location</dt><dd><?= e($c['location']) ?><?php if (!empty($c['maps_url'])): ?> · <a href="<?= e($c['maps_url']) ?>" target="_blank" rel="noopener">map ↗</a><?php endif; ?></dd></div>
<?php endif; ?>
          <div><dt>Office hours</dt><dd><?= e($c['office_hours']) ?></dd></div>
          <div><dt>Moodle</dt><dd><?php if (!empty($c['moodle_url'])): ?><a href="<?= e($c['moodle_url']) ?>" target="_blank" rel="noopener">official course page ↗</a><?php else: ?>available soon<?php endif; ?></dd></div>
          <div><dt>Exam</dt><dd><a href="exam.php">assessment rules</a></dd></div>
        </dl>
      </section>

<?php page_footer();
