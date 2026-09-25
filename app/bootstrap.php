<?php
/* ─── Shared bootstrap — config, session, database, auth, course content ──────
   Every page in public/ starts with  require __DIR__ . '/../app/bootstrap.php';
   Nothing in app/, content/, materials/ or data/ is reachable from the web:
   the Plesk document root is public/. */

declare(strict_types=1);

const ROOT          = __DIR__ . '/..';
const MATERIALS_DIR = ROOT . '/materials';
const DATA_DIR      = ROOT . '/data';

date_default_timezone_set('Europe/Rome');

/* ── Config ────────────────────────────────────────────────────────────────── */

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing app/config.php — copy app/config.sample.php and edit it.');
}
$CONFIG = require $configFile;

function config(string $key)
{
    global $CONFIG;
    return $CONFIG[$key] ?? null;
}

/* ── Session (stored in data/sessions so the host's garbage collector for
      other sites cannot log students out after the default 24 minutes) ────── */

$sessionDir = DATA_DIR . '/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0700, true);
}
$lifetime = (int) (config('session_days') ?? 14) * 86400;
$https    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

ini_set('session.save_path', $sessionDir);
ini_set('session.gc_maxlifetime', (string) $lifetime);
ini_set('session.use_strict_mode', '1');
session_name('pel_session');
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

/* ── Database ──────────────────────────────────────────────────────────────── */

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $pdo = new PDO('sqlite:' . DATA_DIR . '/pel.sqlite', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name    TEXT NOT NULL,
            last_name     TEXT NOT NULL,
            email         TEXT NOT NULL UNIQUE COLLATE NOCASE,
            matricola     TEXT NOT NULL,
            programme     TEXT NOT NULL,
            attending     INTEGER NOT NULL DEFAULT 1,
            password_hash TEXT NOT NULL,
            is_active     INTEGER NOT NULL DEFAULT 1,
            created_at    TEXT NOT NULL,
            last_login_at TEXT,
            login_count   INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE IF NOT EXISTS login_attempts (
            ip TEXT NOT NULL,
            ts INTEGER NOT NULL
        );
        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );'
    );
    return $pdo;
}

function setting(string $key, ?string $default = null): ?string
{
    $st = db()->prepare('SELECT value FROM settings WHERE key = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function set_setting(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?)
                   ON CONFLICT(key) DO UPDATE SET value = excluded.value')
        ->execute([$key, $value]);
}

function access_code(): string
{
    return setting('access_code', (string) config('access_code'));
}

function registration_open(): bool
{
    return setting('registration_open', '1') === '1';
}

/* ── Helpers ───────────────────────────────────────────────────────────────── */

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('The form expired. Go back, reload the page and try again.');
    }
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/* Throttle: at most 10 failed logins or registrations per IP per 15 minutes. */
function too_many_attempts(): bool
{
    db()->prepare('DELETE FROM login_attempts WHERE ts < ?')->execute([time() - 900]);
    $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ?');
    $st->execute([client_ip()]);
    return (int) $st->fetchColumn() >= 10;
}

function record_failed_attempt(): void
{
    db()->prepare('INSERT INTO login_attempts (ip, ts) VALUES (?, ?)')
        ->execute([client_ip(), time()]);
}

/* ── Auth ──────────────────────────────────────────────────────────────────── */

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $st->execute([$_SESSION['uid']]);
        $user = $st->fetch() ?: null;
        if (!$user) {
            unset($_SESSION['uid']);
        }
    }
    return $user;
}

function is_admin(?array $user = null): bool
{
    $user = $user ?? current_user();
    if (!$user) {
        return false;
    }
    $admins = array_map('strtolower', config('admin_emails') ?? []);
    return in_array(strtolower($user['email']), $admins, true);
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        // remember only real page visits (not favicon or asset requests)
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && preg_match('~^/[\w-]+\.php(\?|$)~', $uri)) {
            $_SESSION['after_login'] = $uri;
        }
        redirect('login.php');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if (!is_admin($user)) {
        http_response_code(403);
        exit('Forbidden');
    }
    return $user;
}

function log_in(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $user['id'];
    db()->prepare('UPDATE users SET last_login_at = ?, login_count = login_count + 1 WHERE id = ?')
        ->execute([now(), $user['id']]);
}

/* ── Course content (content/course.json) ──────────────────────────────────── */

function course(): array
{
    static $c = null;
    if ($c === null) {
        $c = json_decode((string) file_get_contents(ROOT . '/content/course.json'), true);
        if (!is_array($c)) {
            http_response_code(500);
            exit('content/course.json is not valid JSON: ' . json_last_error_msg());
        }
    }
    return $c;
}

/* An item with "available_from" stays hidden from students until that date;
   the admin sees it early, marked as scheduled. */
function item_available(array $item): bool
{
    return empty($item['available_from']) || date('Y-m-d') >= $item['available_from'];
}

/* Every item in course.json, wherever it sits (weeks, exercises, exam, …). */
function all_items(): array
{
    $out  = [];
    $walk = function ($node) use (&$walk, &$out) {
        if (!is_array($node)) {
            return;
        }
        if (isset($node['file']) || isset($node['url'])) {
            $out[] = $node;
        }
        foreach ($node as $child) {
            $walk($child);
        }
    };
    $walk(course());
    return $out;
}

const ITEM_LABELS = [
    'slides'   => 'Slides',
    'dataset'  => 'Data',
    'code'     => 'Code',
    'exercise' => 'Exercise',
    'solution' => 'Solution',
    'reading'  => 'Reading',
    'exam'     => 'Exam',
    'link'     => 'Link',
    'video'    => 'Video',
];

/* One pill link, same markup as the slide links on teaching.html. */
function item_link(array $item): string
{
    $admin = is_admin();
    if (!item_available($item) && !$admin) {
        return '';
    }
    $type  = ITEM_LABELS[$item['type'] ?? ''] ?? '';
    $label = ($type ? $type . ' · ' : '') . ($item['label'] ?? '');
    if (!empty($item['file'])) {
        $href = 'file.php?f=' . rawurlencode($item['file']);
    } else {
        $href = $item['url'];
    }
    $sched = !item_available($item)
        ? ' <span class="pel-sched">from ' . e($item['available_from']) . '</span>'
        : '';
    return '<a class="paper-link" href="' . e($href) . '" target="_blank" rel="noopener">'
         . e($label) . '</a>' . $sched;
}

function item_links(array $items): string
{
    $html = implode("\n", array_filter(array_map('item_link', $items)));
    return $html === '' ? '' : '<div class="paper-links">' . $html . '</div>';
}
