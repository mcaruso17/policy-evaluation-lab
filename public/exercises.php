<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

require_login();
$sets = course()['exercises'];

page_header('Exercises', 'exercises.php');
page_head('Exercises', 'Problem sets, lab exercises and interactive practice. Solutions are released after each set is discussed in class.');
?>

<?php foreach ($sets as $s): ?>
      <p class="section-label"><?= e($s['title']) ?></p>
      <div class="course-item">
<?php if (!empty($s['description'])): ?>
        <p class="course-desc"><?= $s['description'] ?></p>
<?php endif; ?>
<?php $links = item_links($s['items'] ?? []); ?>
        <?= $links !== '' ? $links : '<p class="pel-muted">Coming soon.</p>' ?>
      </div>
<?php endforeach; ?>

<?php page_footer();
