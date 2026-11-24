<?php
/**
 * Database connection helper.
 * Credentials come from environment variables (set in docker-compose.yml),
 * never hardcoded here.
 */

function get_pdo(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'student_management_lab';
    $user = getenv('DB_USER') ?: 'labapp_user';
    $pass = getenv('DB_PASSWORD') ?: '';

    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        // Do not leak connection details to the browser.
        error_log('DB connection error: ' . $e->getMessage());
        die('Database connection failed. Please check server logs / docker-compose status.');
    }

    return $pdo;
}

/** Which app flavor is running: "vulnerable" or "secure". */
function app_mode(): string
{
    $mode = getenv('APP_MODE') ?: 'vulnerable';
    return in_array($mode, ['vulnerable', 'secure'], true) ? $mode : 'vulnerable';
}

function is_vulnerable_mode(): bool
{
    return app_mode() === 'vulnerable';
}
