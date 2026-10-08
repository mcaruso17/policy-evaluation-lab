<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

require_login();
$groups = course()['resources'];

page_header('Resources', 'resources.php');
page_head('Resources', 'Textbooks, software, data and interactive tools used in the course.');
?>

<?php foreach ($groups as $g): ?>
      <section class="pel-split pel-split-tight">
        <h2 class="pel-split-title"><?= e($g['group']) ?></h2>
        <?= item_rows($g['items'] ?? []) ?>
      </section>
<?php endforeach; ?>

<?php page_footer();
