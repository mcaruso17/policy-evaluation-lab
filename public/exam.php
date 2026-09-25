<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

require_login();
$exam = course()['exam'];

page_header('Exam', 'exam.php');
page_head('Exam', $exam['intro'] ?? '');
?>

      <p class="section-label">Assessment</p>
<?php foreach ($exam['parts'] ?? [] as $p): ?>
      <div class="course-item">
        <p class="course-title"><?= e($p['title']) ?></p>
        <p class="course-desc"><?= $p['description'] ?></p>
      </div>
<?php endforeach; ?>

      <p class="section-label">Exam sessions</p>
<?php if (empty($exam['sessions'])): ?>
      <p class="pel-muted pel-pad">Dates will be published here and on the university portal.</p>
<?php endif; ?>
<?php foreach ($exam['sessions'] ?? [] as $s): ?>
      <div class="course-item">
        <p class="course-title"><?= e($s['date']) ?></p>
        <p class="course-meta"><?= e($s['details'] ?? '') ?></p>
      </div>
<?php endforeach; ?>

      <p class="section-label">Past exams and sample questions</p>
      <div class="course-item">
<?php $links = item_links($exam['items'] ?? []); ?>
        <?= $links !== '' ? $links : '<p class="pel-muted">Coming soon.</p>' ?>
      </div>

<?php page_footer();
