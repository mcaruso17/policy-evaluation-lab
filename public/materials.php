<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';

require_login();
$weeks = course()['weeks'];

page_header('Lectures', 'materials.php');
page_head('Lectures', 'Week by week: slides go up before each lecture, datasets and code after the lab.');
?>

<?php $cw = current_week(); ?>
      <div class="pel-cards">
<?php foreach ($weeks as $w):
    $now   = $cw === (int) $w['week'];
    $items = item_rows($w['items'] ?? []); ?>
        <article class="pel-card<?= $now ? ' pel-card-now' : '' ?>" id="week-<?= (int) $w['week'] ?>">
          <p class="pel-kicker">Week <?= (int) $w['week'] ?><?= $now ? ' <span class="pel-now">this week</span>' : '' ?></p>
          <p class="pel-card-title"><?= e($w['topic']) ?></p>
<?php if (!empty($w['summary'])): ?>
          <p class="pel-card-text"><?= $w['summary'] ?></p>
<?php endif; ?>
          <dl class="pel-meta">
            <div><dt>Dates</dt><dd><?= e($w['dates'] ?? '') ?></dd></div>
            <div><dt>Material</dt><dd><?= $items === '' ? 'coming soon' : ($k = substr_count($items, '<li>')) . ($k === 1 ? ' item' : ' items') ?></dd></div>
          </dl>
          <?= $items ?>
        </article>
<?php endforeach; ?>
      </div>

<?php page_footer();
