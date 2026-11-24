<?php
/**
 * Session / auth helpers shared by both app modes.
 * Session cookie hardening and session-fixation protection are only
 * fully applied in "secure" mode — see lab/secure/login.php and the
 * is_vulnerable_mode() branches below for the documented difference.
 */

require_once __DIR__ . '/db.php';

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (is_vulnerable_mode()) {
        // VULNERABLE: default session cookie params (no HttpOnly/Secure/
        // SameSite hardening beyond PHP defaults). Documented in
        // docs/vulnerabilities.md under "Authentication Weaknesses".
        session_start();
    } else {
        // SECURE: hardened cookie params.
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            // 'secure' => true, // enable when served over HTTPS
        ]);
        session_start();
    }
}

function current_user(): ?array
{
    start_app_session();
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        die('Forbidden: admin access required.');
    }
    return $user;
}

function log_audit(?int $userId, string $action, string $details = ''): void
{
    try {
        $pdo = get_pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {
        error_log('audit log failed: ' . $e->getMessage());
    }
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
