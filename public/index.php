<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

$user = require_login();
$data = course();
$c    = $data['course'];

page_header('Home', 'index.php');
page_head($c['title'], e($c['university']) . ' — ' . e($c['department']) . ' · ' . e($c['year']) . '<br>' . e($c['details']));
?>

      <p class="section-label">Announcements</p>
<?php if (empty($data['announcements'])): ?>
      <p class="pel-muted pel-pad">No announcements yet.</p>
<?php endif; ?>
<?php foreach ($data['announcements'] as $a): ?>
      <div class="course-item">
        <p class="course-meta"><?= e(date('j F Y', strtotime($a['date']))) ?></p>
        <p class="course-title"><?= e($a['title']) ?></p>
        <p class="course-desc"><?= $a['body'] /* trusted HTML from course.json */ ?></p>
      </div>
<?php endforeach; ?>

      <p class="section-label">The course</p>
      <div class="course-item">
        <p class="course-desc">
          <strong>Lecturer:</strong> <?= e($c['lecturer']) ?> ·
          <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a><br>
          <strong>Timetable:</strong> <?= e($c['timetable']) ?><br>
<?php if (!empty($c['location'])): ?>
          <strong>Location:</strong> <?= e($c['location']) ?><?php if (!empty($c['maps_url'])): ?> · <a href="<?= e($c['maps_url']) ?>" target="_blank" rel="noopener">map ↗</a><?php endif; ?><br>
<?php endif; ?>
          <strong>Office hours:</strong> <?= e($c['office_hours']) ?>
<?php if (!empty($c['moodle_url'])): ?>
          <br><strong>Moodle:</strong> <a href="<?= e($c['moodle_url']) ?>" target="_blank" rel="noopener">official course page ↗</a>
<?php endif; ?>
        </p>
        <div class="paper-links">
          <a class="paper-link" href="materials.php">Lectures</a>
          <a class="paper-link" href="exercises.php">Exercises</a>
          <a class="paper-link" href="exam.php">Exam</a>
          <a class="paper-link" href="resources.php">Resources</a>
        </div>
      </div>

<?php page_footer();
