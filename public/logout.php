<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
$user = current_user();
if ($user) {
    log_audit($user['id'], 'logout', 'User logged out');
}
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
