<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';
require __DIR__ . '/../app/survey.php';

/* Summary of the anonymous questionnaire, like a Google Forms summary.
   The lecturer always sees it (charts + free-text answers); students see the
   charts only, once "Results visible to students" is ticked in the admin page.
   Add ?live=1 to refresh the page every 15 seconds while students answer. */

$user  = require_login();
$admin = is_admin($user);
survey_init();

if (!$admin && setting('survey_results_public', '0') !== '1') {
    http_response_code(403);
    page_header('Questionnaire results');
    page_head('Questionnaire results', 'The results are not available yet: they will be shown in class.');
    page_footer();
    exit;
}

$rows = db()->query('SELECT * FROM survey_responses ORDER BY id')->fetchAll();
$n    = count($rows);
$live = !empty($_GET['live']) && $admin;

/* Counts per option. Multi-select questions are counted per respondent, so
   percentages can add up to more than 100% (as in Google Forms). */
function survey_counts(array $rows, string $field, array $options, bool $sort): array
{
    $c = array_fill_keys(array_keys($options), 0);
    foreach ($rows as $r) {
        $keys = array_filter(explode(',', (string) $r[$field]));
        // text typed in an "Other" box counts as "Other" even if the box was not ticked
        if (isset($options['other']) && trim((string) ($r[$field . '_other'] ?? '')) !== '' && $field !== 'background') {
            $keys[] = 'other';
        }
        foreach (array_unique($keys) as $k) {
            if (isset($c[$k])) {
                $c[$k]++;
            }
        }
    }
    if ($sort) {
        arsort($c);
    }
    return $c;
}

$scale = array_combine(range(1, 5), ['1 — not at all', '2', '3', '4', '5 — very confident']);
$questions = [
    ['What did you study before?', survey_counts($rows, 'background', SURVEY_BACKGROUND, false), SURVEY_BACKGROUND, false, 'background_other'],
    ['Which areas of economics interest you most?', survey_counts($rows, 'interests', SURVEY_INTERESTS, true), SURVEY_INTERESTS, true, 'interests_other'],
    ['Confidence in mathematics', survey_counts($rows, 'conf_maths', $scale, false), $scale, false, null, 'conf_maths'],
    ['Confidence in statistics', survey_counts($rows, 'conf_stats', $scale, false), $scale, false, null, 'conf_stats'],
    ['Courses taken before', survey_counts($rows, 'courses', SURVEY_COURSES, false), SURVEY_COURSES, true, null],
    ['Professional goals after the degree', survey_counts($rows, 'goals', SURVEY_GOALS, true), SURVEY_GOALS, true, 'goals_other'],
];

$avg = fn(string $f) => $n ? number_format(array_sum(array_column($rows, $f)) / $n, 1) : '—';

if ($live) {
    header('Refresh: 15');
}
page_header('Questionnaire results', $admin ? 'admin.php' : '');
page_head('Getting to know you: results',
          $n . ' anonymous ' . ($n === 1 ? 'answer' : 'answers') . ' so far.'
          . ($live ? ' <span class="pel-sched">live · refreshes every 15 s</span>' : ''));
?>

      <div class="pel-stats">
        <div><span class="pel-stat-num"><?= $n ?></span><span class="pel-stat-label">answers</span></div>
        <div><span class="pel-stat-num"><?= $avg('conf_maths') ?></span><span class="pel-stat-label">average confidence in maths (1–5)</span></div>
        <div><span class="pel-stat-num"><?= $avg('conf_stats') ?></span><span class="pel-stat-label">average confidence in statistics (1–5)</span></div>
      </div>

<?php if ($admin): ?>
      <p class="pel-muted">
        <a class="paper-link" href="survey-results.php<?= $live ? '' : '?live=1' ?>"><?= $live ? 'Stop live mode' : 'Live mode (refresh every 15 s)' ?></a>
        <a class="paper-link" href="admin.php?export=survey">Download answers (CSV)</a>
        <a class="paper-link" href="admin.php">Back to Students</a>
      </p>
<?php endif; ?>

<?php if (!$n): ?>
      <p class="pel-muted pel-pad">No answers yet.</p>
<?php else: ?>
<?php foreach ($questions as $q):
    [$title, $counts, $options, $multi] = $q;
    $max = max($counts) ?: 1; ?>
      <section class="pel-chart">
        <p class="pel-chart-title"><?= e($title) ?></p>
        <p class="pel-chart-sub"><?= $multi ? 'More than one answer allowed: percentages are shares of respondents.' : $n . ' answers' ?><?= isset($q[5]) ? ' · average ' . $avg($q[5]) : '' ?></p>
<?php foreach ($counts as $k => $c):
        $pct = round(100 * $c / $n); ?>
        <div class="pel-bar">
          <span class="pel-bar-label"><?= e($options[$k]) ?></span>
          <span class="pel-bar-track"><span class="pel-bar-fill" style="width: <?= round(100 * $c / $max, 1) ?>%"></span></span>
          <span class="pel-bar-value"><?= $c ?> <span class="pel-muted">(<?= $pct ?>%)</span></span>
        </div>
<?php endforeach; ?>
<?php if ($admin && !empty($q[4])):
        $other = array_values(array_filter(array_column($rows, $q[4]), fn($t) => trim((string) $t) !== ''));
        if ($other): ?>
        <details class="pel-other">
          <summary>Free-text details (<?= count($other) ?>) · only you can see these</summary>
          <ul>
<?php foreach ($other as $t): ?>
            <li><?= e($t) ?></li>
<?php endforeach; ?>
          </ul>
        </details>
<?php endif; endif; ?>
      </section>
<?php endforeach; ?>

<?php if ($admin):
    $comments = array_values(array_filter(array_column($rows, 'comments'), fn($t) => trim((string) $t) !== '')); ?>
      <section class="pel-chart">
        <p class="pel-chart-title">Additional comments</p>
        <details class="pel-other">
          <summary><?= count($comments) ?> comments · only you can see these</summary>
          <ul>
<?php foreach ($comments as $t): ?>
            <li><?= nl2br(e($t)) ?></li>
<?php endforeach; ?>
          </ul>
        </details>
      </section>
<?php endif; ?>
<?php endif; ?>

<?php page_footer();
