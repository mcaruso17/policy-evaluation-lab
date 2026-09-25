<?php
/* Serves a file from materials/ to logged-in students. Only files listed in
   content/course.json can be downloaded, and only once their available_from
   date has passed, so an unreleased solution cannot be fetched by guessing
   its name. */
require __DIR__ . '/../app/bootstrap.php';

require_login();
$f = (string) ($_GET['f'] ?? '');

$item = null;
foreach (all_items() as $it) {
    if (($it['file'] ?? null) === $f) {
        $item = $it;
        break;
    }
}

$path = realpath(MATERIALS_DIR . '/' . $f);
$base = realpath(MATERIALS_DIR);
if (!$item || !$path || !$base || !str_starts_with($path, $base . DIRECTORY_SEPARATOR) || !is_file($path)) {
    http_response_code(404);
    exit('File not found.');
}
if (!item_available($item) && !is_admin()) {
    http_response_code(403);
    exit('This file is not available yet.');
}

$types = [
    'pdf'  => 'application/pdf',
    'dta'  => 'application/octet-stream',
    'csv'  => 'text/csv; charset=utf-8',
    'do'   => 'text/plain; charset=utf-8',
    'r'    => 'text/plain; charset=utf-8',
    'txt'  => 'text/plain; charset=utf-8',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'zip'  => 'application/zip',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
];
$ext    = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$inline = in_array($ext, ['pdf', 'png', 'jpg'], true);

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename($path) . '"');
header('Cache-Control: private, max-age=3600');
readfile($path);
