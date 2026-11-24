<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
$user = require_login();

$file = basename((string)($_GET['file'] ?? ''));
if ($file === '') {
    http_response_code(400);
    die('Missing file parameter.');
}

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT * FROM uploads WHERE stored_filename = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$file]);
$upload = $stmt->fetch();

if (!$upload) {
    http_response_code(404);
    die('Not found.');
}

// Only the uploader themselves, or an admin, may view the file.
if ($user['role'] !== 'admin' && (int)$upload['user_id'] !== (int)$user['id']) {
    http_response_code(403);
    die('Forbidden.');
}

$path = __DIR__ . '/../secure_uploads/' . $file;
if (!is_file($path)) {
    http_response_code(404);
    die('Not found.');
}

header('Content-Type: ' . ($upload['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . $file . '"');
readfile($path);
