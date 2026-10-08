<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/layout.php';
require __DIR__ . '/../app/survey.php';

/* Public on purpose: students scan the QR code in class and answer without
   logging in, so the answers cannot be linked to an account. */

survey_init();
$errors = [];
$v = [
    'background' => '', 'background_other' => '', 'interests' => [], 'interests_other' => '',
    'conf_maths' => '', 'conf_stats' => '', 'courses' => [], 'goals' => [], 'goals_other' => '',
    'comments' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && survey_open() && empty($_SESSION['survey_done'])) {
    check_csrf();
    $v['background']       = (string) ($_POST['background'] ?? '');
    $v['background_other'] = survey_text('background_other', 200);
    $v['interests']        = survey_pick((array) ($_POST['interests'] ?? []), SURVEY_INTERESTS);
    $v['interests_other']  = survey_text('interests_other', 300);
    $v['conf_maths']       = (string) ($_POST['conf_maths'] ?? '');
    $v['conf_stats']       = (string) ($_POST['conf_stats'] ?? '');
    $v['courses']          = survey_pick((array) ($_POST['courses'] ?? []), SURVEY_COURSES);
    $v['goals']            = survey_pick((array) ($_POST['goals'] ?? []), SURVEY_GOALS);
    $v['goals_other']      = survey_text('goals_other', 300);
    $v['comments']         = survey_text('comments', 2000);

    if (!isset(SURVEY_BACKGROUND[$v['background']])) {
        $errors[] = 'Tell us what you studied before.';
    }
    if (!$v['interests'] && $v['interests_other'] === '') {
        $errors[] = 'Choose at least one topic you are interested in.';
    }
    foreach (['conf_maths' => 'maths', 'conf_stats' => 'statistics'] as $k => $label) {
        if (!in_array($v[$k], ['1', '2', '3', '4', '5'], true)) {
            $errors[] = "Rate how confident you feel about $label.";
        }
    }
    if (!$v['courses']) {
        $errors[] = 'Tell us which courses you took before (or "None of these").';
    }
    if (!$v['goals'] && $v['goals_other'] === '') {
        $errors[] = 'Choose at least one professional goal (or "Not sure yet").';
    }

    if (!$errors) {
        db()->prepare(
            'INSERT INTO survey_responses (submitted_on, background, background_other, interests, interests_other,
                                           conf_maths, conf_stats, courses, goals, goals_other, comments)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            date('Y-m-d'), $v['background'], $v['background_other'],
            implode(',', $v['interests']), $v['interests_other'],
            (int) $v['conf_maths'], (int) $v['conf_stats'],
            implode(',', $v['courses']), implode(',', $v['goals']), $v['goals_other'], $v['comments'],
        ]);
        $_SESSION['survey_done'] = true;
        redirect('survey.php');
    }
}

/* Checkbox / radio helpers */
function survey_checked(bool $on): string
{
    return $on ? ' checked' : '';
}

page_header('Questionnaire', 'survey.php');
page_head('Getting to know you',
          'A short, <strong>anonymous</strong> questionnaire: no name, e-mail or student ID is collected. '
          . 'It helps tailor the course to your background and interests. It takes about two minutes.');
?>

<?php if (!empty($_SESSION['survey_done'])): ?>
      <div class="callout callout-info"><p><strong>Thank you!</strong> Your answers have been recorded anonymously.
        We will look at the overall results together in class.</p></div>

<?php elseif (!survey_open()): ?>
      <div class="callout callout-warn"><p>The questionnaire is closed. Thank you for your interest.</p></div>

<?php else: ?>

<?php if ($errors): ?>
      <div class="callout callout-danger">
<?php foreach ($errors as $err): ?>
        <p><?= e($err) ?></p>
<?php endforeach; ?>
      </div>
<?php endif; ?>

      <form method="post" class="pel-form pel-survey">
        <?= csrf_field() ?>

        <fieldset class="pel-radio">
          <legend>1. What did you study before this Master's degree?</legend>
<?php foreach (SURVEY_BACKGROUND as $k => $label): ?>
          <label><input type="radio" name="background" value="<?= e($k) ?>"<?= survey_checked($v['background'] === $k) ?>> <?= e($label) ?></label>
<?php endforeach; ?>
          <input type="text" name="background_other" value="<?= e($v['background_other']) ?>" maxlength="200"
                 placeholder="If other, or to add details (e.g. degree name)">
        </fieldset>

        <fieldset class="pel-radio">
          <legend>2. Which areas of economics interest you most? (choose all that apply)</legend>
<?php foreach (SURVEY_INTERESTS as $k => $label): ?>
          <label><input type="checkbox" name="interests[]" value="<?= e($k) ?>"<?= survey_checked(in_array($k, $v['interests'], true)) ?>> <?= e($label) ?></label>
<?php endforeach; ?>
          <input type="text" name="interests_other" value="<?= e($v['interests_other']) ?>" maxlength="300"
                 placeholder="If other: which topic or policy would you like to study?">
        </fieldset>

<?php foreach (['conf_maths' => '3. How confident do you feel about mathematics?',
                'conf_stats' => '4. How confident do you feel about statistics?'] as $k => $q): ?>
        <fieldset class="pel-radio pel-scale">
          <legend><?= e($q) ?></legend>
          <span class="pel-muted">Not at all</span>
<?php for ($i = 1; $i <= 5; $i++): ?>
          <label><input type="radio" name="<?= $k ?>" value="<?= $i ?>"<?= survey_checked($v[$k] === (string) $i) ?>> <?= $i ?></label>
<?php endfor; ?>
          <span class="pel-muted">Very confident</span>
        </fieldset>
<?php endforeach; ?>

        <fieldset class="pel-radio">
          <legend>5. Which of these courses have you taken before? (choose all that apply)</legend>
<?php foreach (SURVEY_COURSES as $k => $label): ?>
          <label><input type="checkbox" name="courses[]" value="<?= e($k) ?>"<?= survey_checked(in_array($k, $v['courses'], true)) ?>> <?= e($label) ?></label>
<?php endforeach; ?>
        </fieldset>

        <fieldset class="pel-radio">
          <legend>6. What are your professional goals after the degree? (choose all that apply)</legend>
<?php foreach (SURVEY_GOALS as $k => $label): ?>
          <label><input type="checkbox" name="goals[]" value="<?= e($k) ?>"<?= survey_checked(in_array($k, $v['goals'], true)) ?>> <?= e($label) ?></label>
<?php endforeach; ?>
          <input type="text" name="goals_other" value="<?= e($v['goals_other']) ?>" maxlength="300"
                 placeholder="If other, or to add details">
        </fieldset>

        <label>7. Additional comments (optional): expectations, topics you would like to see, anything else
          <textarea name="comments" rows="4" maxlength="2000"><?= e($v['comments']) ?></textarea>
        </label>

        <button type="submit" class="btn btn-primary">Send anonymously</button>
      </form>
<?php endif; ?>

<?php page_footer();
