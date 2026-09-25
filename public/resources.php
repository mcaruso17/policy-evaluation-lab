<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

require_login();
$groups = course()['resources'];

page_header('Resources', 'resources.php');
page_head('Resources', 'Textbooks, software, data and interactive tools used in the course.');
?>

<?php foreach ($groups as $g): ?>
      <p class="section-label"><?= e($g['group']) ?></p>
      <div class="course-item">
        <?= item_links($g['items'] ?? []) ?>
      </div>
<?php endforeach; ?>

<?php page_footer();
