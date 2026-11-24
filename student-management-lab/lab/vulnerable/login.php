<?php
/**
 * VULNERABLE login handler.
 *
 * Vulnerabilities demonstrated here (see docs/vulnerabilities.md):
 *  - SQL Injection: username/password are concatenated directly into
 *    the SQL string instead of using bound parameters.
 *  - Account enumeration: distinct error messages reveal whether a
 *    username exists.
 *  - No login rate limiting: unlimited attempts are allowed.
 *  - Session ID is not regenerated after login (session fixation risk).
 *
 * sqlmap target:
 *   POST login.php  body: username=admin&password=test
 *   Injectable parameters: username, password
 */

function attempt_login(string $username, string $password): array
{
    $pdo = get_pdo();

    // --- VULNERABLE: direct string concatenation, no prepared statement ---
    $sql = "SELECT * FROM users WHERE username = '{$username}' AND password_hash IS NOT NULL LIMIT 1";
    // Note: password is intentionally NOT included in the WHERE clause here;
    // it is checked in PHP below. This keeps the classic
    // ' OR '1'='1 style bypass meaningful for demonstration while still
    // allowing sqlmap to enumerate the database through this parameter.
    // A second, equally vulnerable query form is used when "password" is
    // the attack target (see note below).
    try {
        $stmt = $pdo->query($sql);
        $row = $stmt ? $stmt->fetch() : false;
    } catch (PDOException $e) {
        // Vulnerable apps often leak DB errors - documented, not "fixed" here.
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage(), 'enum' => null];
    }

    if (!$row) {
        // Account enumeration: distinguish "no such user" ...
        return ['success' => false, 'error' => 'Username not found.', 'enum' => 'no_user'];
    }

    if (!password_verify($password, $row['password_hash']) && $password !== ($row['plain_password'] ?? null)) {
        // ... from "wrong password for an existing user".
        return ['success' => false, 'error' => 'Incorrect password for this account.', 'enum' => 'bad_password'];
    }

    // No rate limiting / lockout applied in vulnerable mode.
    return ['success' => true, 'user' => $row];
}

/**
 * A second vulnerable query, reachable via a dedicated GET/POST parameter,
 * kept intentionally concatenated so sqlmap has a clean single-parameter
 * injection point separate from the login form's business logic above.
 *
 *   GET/POST login.php?check_user=admin
 */
function vulnerable_user_lookup(string $usernameParam): array
{
    $pdo = get_pdo();
    $sql = "SELECT id, username, role, email FROM users WHERE username = '{$usernameParam}'";
    try {
        $stmt = $pdo->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    } catch (PDOException $e) {
        return [];
    }
}
