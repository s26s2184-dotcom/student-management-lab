<?php
/**
 * SECURE login handler — mitigations applied:
 *  - PDO prepared statement with bound parameters (no SQL injection).
 *  - Generic error message regardless of whether the username exists
 *    (no account enumeration).
 *  - Simple in-session rate limiting / lockout after repeated failures.
 *  - Session ID regenerated after successful authentication.
 */

const MAX_ATTEMPTS = 5;
const LOCKOUT_SECONDS = 60;

function attempt_login(string $username, string $password): array
{
    start_app_session();

    $key = 'login_attempts_' . sha1(strtolower($username));
    $now = time();
    $state = $_SESSION[$key] ?? ['count' => 0, 'first' => $now];

    if ($state['count'] >= MAX_ATTEMPTS && ($now - $state['first']) < LOCKOUT_SECONDS) {
        $wait = LOCKOUT_SECONDS - ($now - $state['first']);
        return ['success' => false, 'error' => "Too many attempts. Try again in {$wait}s."];
    }
    if (($now - $state['first']) >= LOCKOUT_SECONDS) {
        $state = ['count' => 0, 'first' => $now];
    }

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u AND is_active = 1 LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();

    $valid = $row && password_verify($password, $row['password_hash']);

    if (!$valid) {
        $state['count']++;
        $_SESSION[$key] = $state;
        // Generic message: does not reveal whether the username exists.
        return ['success' => false, 'error' => 'Invalid username or password.'];
    }

    unset($_SESSION[$key]);

    // Prevent session fixation: issue a brand new session ID on login.
    session_regenerate_id(true);

    return ['success' => true, 'user' => $row];
}
