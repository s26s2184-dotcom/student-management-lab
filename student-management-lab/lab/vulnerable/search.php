<?php
/**
 * VULNERABLE student search.
 *
 * Vulnerabilities:
 *  - SQL Injection via the "q" GET parameter (string concatenation).
 *  - Reflected XSS: the raw "q" value is echoed back into the page
 *    without output encoding (see public/students.php rendering).
 *
 * sqlmap target:
 *   GET students.php?q=test
 *   Injectable parameter: q
 *
 * Reflected XSS PoC:
 *   students.php?q=<script>alert('XSS')</script>
 *   students.php?q=<img src=x onerror="document.body.style.background='red'">
 */

function vulnerable_student_search(string $q): array
{
    $pdo = get_pdo();

    // --- VULNERABLE: untrusted input concatenated directly into SQL ---
    $sql = "SELECT id, student_code, full_name, major, enrollment_year
            FROM students
            WHERE full_name LIKE '%{$q}%' OR student_code LIKE '%{$q}%'
            ORDER BY id";

    try {
        $stmt = $pdo->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    } catch (PDOException $e) {
        // Error message intentionally surfaced for teaching error-based SQLi.
        return ['__error__' => $e->getMessage()];
    }
}
