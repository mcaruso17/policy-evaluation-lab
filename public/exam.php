<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

require_login();
$exam = course()['exam'];

page_header('Exam', 'exam.php');
page_head('Exam', $exam['intro'] ?? '');
?>

<?php section_title('Assessment'); ?>
      <div class="pel-cards pel-cards-3">
<?php foreach ($exam['parts'] ?? [] as $i => $p): ?>
        <article class="pel-card">
          <p class="pel-kicker">Part <?= $i + 1 ?></p>
          <p class="pel-card-title"><?= e($p['title']) ?></p>
          <p class="pel-card-text"><?= $p['description'] ?></p>
        </article>
<?php endforeach; ?>
      </div>

      <section class="pel-split pel-split-tight">
        <h2 class="pel-split-title">Exam sessions</h2>
<?php if (empty($exam['sessions'])): ?>
        <p class="pel-muted">Dates will be published here and on the university portal.</p>
<?php else: ?>
        <dl class="pel-list">
<?php foreach ($exam['sessions'] as $s): ?>
          <div><dt><?= e($s['date']) ?></dt><dd><?= e($s['details'] ?? '') ?></dd></div>
<?php endforeach; ?>
        </dl>
<?php endif; ?>
      </section>

      <section class="pel-split pel-split-tight">
        <h2 class="pel-split-title">Past exams and sample questions</h2>
<?php $rows = item_rows($exam['items'] ?? []); ?>
        <?= $rows !== '' ? $rows : '<p class="pel-muted">Coming soon.</p>' ?>
      </section>

<?php page_footer();
