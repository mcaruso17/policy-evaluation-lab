<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

require_login();
$weeks = course()['weeks'];

page_header('Lectures', 'materials.php');
page_head('Lectures', 'Week by week: slides go up before each lecture, datasets and code after the lab.');
?>

      <p class="section-label">Programme</p>
<?php foreach ($weeks as $w): ?>
      <div class="course-item" id="week-<?= (int) $w['week'] ?>">
        <p class="course-meta">Week <?= (int) $w['week'] ?><?= !empty($w['dates']) ? ' · ' . e($w['dates']) : '' ?></p>
        <p class="course-title"><?= e($w['topic']) ?></p>
<?php if (!empty($w['summary'])): ?>
        <p class="course-desc"><?= $w['summary'] ?></p>
<?php endif; ?>
        <?= item_links($w['items'] ?? []) ?>
      </div>
<?php endforeach; ?>

<?php page_footer();
