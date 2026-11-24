<?php
/**
 * VULNERABLE student details lookup.
 *
 * Vulnerabilities:
 *  - SQL Injection via the "id" URL parameter (string concatenation,
 *    numeric context — classic sqlmap target).
 *  - IDOR: any logged-in user (not just admin or the owning student)
 *    can view ANY student's full profile just by changing "id" in the
 *    URL. No ownership/role check is performed.
 *
 * sqlmap target:
 *   GET student.php?id=1
 *   Injectable parameter: id (numeric)
 *
 * IDOR PoC:
 *   Log in as jsmith (student_id 1), then browse to student.php?id=4
 *   to view Raj Patel's profile despite not being its owner.
 */

function vulnerable_get_student(string $id): ?array
{
    $pdo = get_pdo();

    // --- VULNERABLE: no prepared statement, no ownership check ---
    $sql = "SELECT s.*, u.username, u.email
            FROM students s
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.id = {$id}";

    try {
        $stmt = $pdo->query($sql);
        $row = $stmt ? $stmt->fetch() : false;
        return $row ?: null;
    } catch (PDOException $e) {
        return ['__error__' => $e->getMessage()];
    }
}
