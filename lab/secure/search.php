<?php
/**
 * SECURE student search — mitigations:
 *  - PDO prepared statement with bound LIKE parameter (no SQL injection).
 *  - Caller must render results with h() / htmlspecialchars (no XSS).
 */

function secure_student_search(string $q): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'SELECT id, student_code, full_name, major, enrollment_year
         FROM students
         WHERE full_name LIKE :q1 OR student_code LIKE :q2
         ORDER BY id'
    );
    $like = '%' . $q . '%';
    $stmt->execute([':q1' => $like, ':q2' => $like]);
    return $stmt->fetchAll();
}
