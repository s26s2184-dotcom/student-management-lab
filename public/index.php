<?php
require_once __DIR__ . '/../includes/auth.php';
start_app_session();
if (current_user()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
