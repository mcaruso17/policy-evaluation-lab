<?php
/* ─── Anonymous first-day questionnaire ───────────────────────────────────────
   Used by public/survey.php (the form) and public/admin.php (results, CSV).
   Answers are anonymous: no user id, name, e-mail or IP address is stored,
   only the day of submission. */

const SURVEY_BACKGROUND = [
    'economics'  => 'Economics',
    'business'   => 'Business or management',
    'stats_math' => 'Statistics, mathematics or data science',
    'social'     => 'Political or social sciences',
    'law'        => 'Law',
    'engineering'=> 'Engineering',
    'other'      => 'Other',
];

const SURVEY_INTERESTS = [
    'labour'      => 'Labour economics',
    'public'      => 'Public economics and taxation',
    'health'      => 'Health economics',
    'education'   => 'Economics of education',
    'development' => 'Development economics',
    'environment' => 'Environmental and energy economics',
    'urban'       => 'Urban and regional economics',
    'io'          => 'Industrial organisation and competition',
    'macro'       => 'Macroeconomics and monetary policy',
    'finance'     => 'Financial economics',
    'behavioural' => 'Behavioural economics',
    'political'   => 'Political economy',
    'inequality'  => 'Inequality, poverty and welfare',
    'gender'      => 'Gender economics',
    'crime'       => 'Economics of crime',
    'other'       => 'Other',
];

const SURVEY_COURSES = [
    'maths'        => 'Mathematics',
    'statistics'   => 'Statistics',
    'econometrics' => 'Econometrics',
    'none'         => 'None of these',
];

const SURVEY_GOALS = [
    'public_admin'  => 'Public administration or policy institutions',
    'intl_org'      => 'International organisations (EU, OECD, UN, World Bank, …)',
    'central_bank'  => 'Central banks or regulatory authorities',
    'consulting'    => 'Consulting',
    'finance'       => 'Banking, finance or insurance',
    'private'       => 'Private companies (business or data analysis)',
    'research'      => 'Research or a PhD',
    'ngo'           => 'NGOs or the non-profit sector',
    'unsure'        => 'Not sure yet',
    'other'         => 'Other',
];

function survey_init(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    db()->exec(
        'CREATE TABLE IF NOT EXISTS survey_responses (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            submitted_on      TEXT NOT NULL,
            background        TEXT NOT NULL,
            background_other  TEXT NOT NULL,
            interests         TEXT NOT NULL,
            interests_other   TEXT NOT NULL,
            conf_maths        INTEGER NOT NULL,
            conf_stats        INTEGER NOT NULL,
            courses           TEXT NOT NULL,
            goals             TEXT NOT NULL,
            goals_other       TEXT NOT NULL,
            comments          TEXT NOT NULL
        )'
    );
    $done = true;
}

function survey_open(): bool
{
    return setting('survey_open', '1') === '1';
}

/* Keep only values that exist in the option list. */
function survey_pick(array $values, array $options): array
{
    return array_values(array_intersect(array_map('strval', $values), array_keys($options)));
}

/* Comma-separated keys -> readable labels. */
function survey_labels(string $keys, array $options): string
{
    $out = [];
    foreach (array_filter(explode(',', $keys)) as $k) {
        $out[] = $options[$k] ?? $k;
    }
    return implode('; ', $out);
}

function survey_text(string $key, int $max): string
{
    return mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $max);
}
