<?php
require __DIR__ . '/../app/bootstrap.php';

$_SESSION = [];
session_destroy();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/']);
redirect('login.php');
