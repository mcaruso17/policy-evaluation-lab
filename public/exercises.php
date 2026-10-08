<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

require_login();
$sets = course()['exercises'];

page_header('Exercises', 'exercises.php');
page_head('Exercises', 'Problem sets, lab exercises and interactive practice. Solutions are released after each set is discussed in class.');
?>

<?php foreach ($sets as $set):
    $rows = item_rows($set['items'] ?? []); ?>
      <section class="pel-split pel-split-tight">
        <div>
          <h2 class="pel-split-title"><?= e($set['title']) ?></h2>
          <p class="pel-split-text"><?= $set['description'] ?></p>
        </div>
        <?= $rows !== '' ? $rows : '<p class="pel-muted">Coming soon.</p>' ?>
      </section>
<?php endforeach; ?>

<?php page_footer();
